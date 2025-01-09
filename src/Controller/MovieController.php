<?php

namespace App\Controller;

use App\Entity\Movie;
use App\Form\MovieFilterType;
use App\Service\TmdbService;
use App\Service\TmdbApiService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;

class MovieController extends AbstractController
{
    private $logger;

    public function __construct(
        private TmdbService $tmdbService,
        private TmdbApiService $tmdbApiService,
        private EntityManagerInterface $entityManager,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    #[Route('/', name: 'movie_index')]
    public function index(Request $request): Response
    {
        $form = $this->createForm(MovieFilterType::class);
        $form->handleRequest($request);

        try {
            if ($form->isSubmitted() && $form->isValid()) {
                $filters = $form->getData();
                // Add debug flash message
                $this->addFlash('debug', 'Form Data: ' . json_encode($filters, JSON_PRETTY_PRINT));

                $apiFilters = [
                    'region' => $filters['region'] ?? null,
                    'genre' => $filters['genre'] ?? null,
                ];

                // Add API filters debug message
                $this->addFlash('debug', 'API Filters: ' . json_encode($apiFilters, JSON_PRETTY_PRINT));

                $result = $this->tmdbApiService->searchMovies('', 1, $apiFilters);
                
                // Add result debug message
                $this->addFlash('debug', 'API Response: ' . json_encode($result, JSON_PRETTY_PRINT));
                
                $movies = $result['results'] ?? [];

                if (empty($movies)) {
                    $this->addFlash('info', 'No movies found matching your criteria. Try different filters.');
                }
            } else {
                $result = $this->tmdbApiService->getPopularMovies();
                $movies = $result['results'] ?? [];
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Error: ' . $e->getMessage());
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
            $movie = $this->tmdbApiService->getMovieDetails($id);
            return $this->render('movie/details.html.twig', [
                'movie' => $movie
            ]);
        } catch (\Exception $e) {
            // dd('Movie not found', [
            //     'error' => $e->getMessage()
            // ]);
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
            $movie->setPoster_path($movieData['poster_path'] ?? null);

            if (!empty($movieData['release_date'])) {
                $movie->setRelease_date(new \DateTime($movieData['release_date']));
            }

            $movie->setVote_average($movieData['vote_average'] ?? null);
            $movie->setVoteCount($movieData['vote_count'] ?? null);
            $movie->setRevenue($movieData['revenue'] ?? null);
            $movie->setGenre($movieData['genres'] ?? null);
            $movie->setOriginalLanguage($movieData['original_language'] ?? null);
            $movie->setProductionCountry($movieData['production_countries'] ?? null);
            $movie->setRuntime($movieData['runtime'] ?? null);

            $this->entityManager->persist($movie);
            $this->entityManager->flush();
        } catch (\Exception $e) {
            // Log the error but don't throw it to prevent breaking the application
            error_log('Error saving movie to database: ' . $e->getMessage());
        }
    }
}