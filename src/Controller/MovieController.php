<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieFilterType;
use App\Service\TmdbService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class MovieController extends AbstractController
{
    public function __construct(
        private TmdbService $tmdbService,
        private EntityManagerInterface $entityManager,
        private HttpClientInterface $httpClient
    ) {}

    #[Route('/', name: 'movie_index')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(MovieFilterType::class);
        $form->handleRequest($request);

        $filters = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $filters = $form->getData();
        }

        // First, try to fetch movies from our local API
        try {
            $apiUrl = $this->generateUrl('_api_/movies{._format}_get_collection', ['_format' => 'jsonld'], 0);
            $queryParams = [];
            
            if (!empty($filters['region'])) {
                $queryParams['productionCountries'] = strtoupper($filters['region']);
            }
            if (!empty($filters['genre'])) {
                $queryParams['genres'] = $filters['genre'];
            }
            if (!empty($filters['year'])) {
                $queryParams['releaseDate'] = [
                    'after' => $filters['year'] . '-01-01',
                    'before' => $filters['year'] . '-12-31'
                ];
            }
            
            $response = $this->httpClient->request('GET', $apiUrl, [
                'query' => $queryParams
            ]);

            $movies = $response->toArray()['hydra:member'];

            // If no movies found in local database, fetch from TMDB and save them
            if (empty($movies)) {
                $tmdbMovies = $this->tmdbService->discoverMoviesWithFilters($filters);
                foreach ($tmdbMovies as $movieData) {
                    $this->saveMovieToDatabase($movieData);
                }

                // Fetch again from local API after saving
                $response = $this->httpClient->request('GET', $apiUrl, [
                    'query' => $queryParams
                ]);
                $movies = $response->toArray()['hydra:member'];
            }
        } catch (\Exception $e) {
            // Log the error and show a user-friendly message
            $this->addFlash('error', 'An error occurred while fetching movies. Please try again later.');
            $movies = [];
        }

        return $this->render('movie/index.html.twig', [
            'form' => $form->createView(),
            'movies' => $movies
        ]);
    }

    #[Route('/movie/{id}', name: 'movie_details')]
    public function details(int $id): Response
    {
        try {
            $apiUrl = $this->generateUrl('_api_/movies/{id}{._format}_get', [
                'id' => $id,
                '_format' => 'jsonld'
            ], 0);
            
            $response = $this->httpClient->request('GET', $apiUrl);
            $movie = $response->toArray();

            return $this->render('movie/details.html.twig', [
                'movie' => $movie
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Movie not found');
            return $this->redirectToRoute('movie_index');
        }
    }

    private function saveMovieToDatabase(array $movieData): void
    {
        try {
            // Check if movie already exists
            $existingMovie = $this->entityManager->getRepository(Movie::class)
                ->findOneBy(['tmdbId' => $movieData['id']]);

            if ($existingMovie) {
                return;
            }

            $movie = new Movie();
            $movie->setTmdbId($movieData['id']);
            $movie->setTitle($movieData['title']);
            $movie->setOverview($movieData['overview'] ?? null);
            $movie->setPosterPath($movieData['poster_path'] ?? null);
            
            if (!empty($movieData['release_date'])) {
                $movie->setReleaseDate(new \DateTime($movieData['release_date']));
            }
            
            $movie->setVoteAverage($movieData['vote_average'] ?? null);
            $movie->setVoteCount($movieData['vote_count'] ?? null);
            $movie->setRevenue($movieData['revenue'] ?? null);
            $movie->setGenres($movieData['genres'] ?? []);
            $movie->setOriginalLanguage($movieData['original_language'] ?? null);
            $movie->setProductionCountries($movieData['production_countries'] ?? []);
            $movie->setBudget($movieData['budget'] ?? null);
            $movie->setRuntime($movieData['runtime'] ?? null);

            $this->entityManager->persist($movie);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Log the error but don't throw it to prevent breaking the application
            error_log('Error saving movie to database: ' . $e->getMessage());
        }
    }
}