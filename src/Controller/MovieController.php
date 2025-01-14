<?php

namespace App\Controller;

use App\Service\TmdbService;
use App\Repository\MovieRepository; 
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class MovieController extends AbstractController
{
    private $tmdbService;

    public function __construct(TmdbService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request, MovieRepository $movieRepository): Response
    {
        $form = $this->createFormBuilder()
            ->add('favorite_dish', ChoiceType::class, [
                'label' => 'Favorite dish',
                'required' => false,
                'choices' => [
                    'Pizza' => 'italy',
                    'Raclette' => 'france',
                    'Sushi' => 'japan',
                    'Burger' => 'usa',
                    'Tajine' => 'alegrie',
                    'Paella' => 'spain'
                ],
            ])
            ->add('holiday', ChoiceType::class, [
                'label' => 'Holiday',
                'required' => false,
                'choices' => [
                    'Mountain' => 'adventure',
                    'Sea' => 'comedy',
                    'Country Side' => 'family',
                    'City' => 'action'
                ],
            ])
            ->add('animal', ChoiceType::class, [
                'label' => 'Animal',
                'required' => false,
                'choices' => [
                    'Cat' => '0',
                    'Dog' => '1',
                    'Horse' => '2',
                    'Elephant' => '3',
                    'Fox' => '4',
                    'Lion' => '5',
                    'Eagle' => '6',
                    'Turtle' => '7',
                    'Shark' => '8',
                    'Whale' => '9'
                ],
            ])
            ->add('transport', ChoiceType::class, [
                'label' => 'Transport',
                'required' => false,
                'choices' => [
                    'Train' => 'vote_average',
                    'Metro' => 'title',
                    'Airplane' => 'popularity',
                    'Bicycle' => 'release_date',
                    'On Foot' => 'revenue',
                    'Wheelchair' => 'vote_count'
                ],
            ])
            ->add('dream_job', ChoiceType::class, [
                'label' => 'Dream Job',
                'required' => false,
                'choices' => [
                    'Astronaut for XSpace' => '1',
                    'Bakery pastry for the President of Republic' => '2',
                    'Veterinarian in Congo' => '3',
                    'Psychiatrist in Harlem' => '4',
                    'Boy Band Singer' => '5',
                    'Santa Claus' => '6',
                    'Voice Actor Dora the explorer' => '7'
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Search'
            ])
            ->getForm();

        $form->handleRequest($request);
        $movies = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $filters = [];

            if (!empty($data['genre'])) {
                $filters['with_genres'] = $data['genre'];
            }
            if (!empty($data['year'])) {
                $filters['primary_release_year'] = $data['year'];
            }

            // Recherche par titre dans la base de données locale
            if (!empty($data['query'])) {
                $movies = $movieRepository->findByTitlePattern($data['query']);
            }

            // Si pas de résultats locaux ou pas de recherche par titre, on cherche via TMDB
            if (empty($movies)) {
                $movies = $this->tmdbService->searchMovies($data['query'] ?? null, null, $filters);
            }
        } else {
            // Afficher les films populaires par défaut
            $movies = $this->tmdbService->discoverMovies();
        }

        return $this->render('movie/index.html.twig', [
            'form' => $form->createView(),
            'movies' => [],
            'tmdbService' => $this->tmdbService,
        ]);
    }

    private function getGenreChoices(): array
    {
        $genres = $this->tmdbService->getGenres();
        $choices = [];

        foreach ($genres as $genre) {
            $choices[$genre['name']] = $genre['id'];
        }

        return $choices;
    }

    #[Route('/movie/{id}', name: 'movie_details')]
    public function showMovieDetails(int $id): Response
    {
        $movie = $this->tmdbService->getMovieDetails($id);

        return $this->render('movie/details.html.twig', [
            'movie' => $movie,
            'tmdbService' => $this->tmdbService,
        ]);
    }
}