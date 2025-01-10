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
            ->add('query', TextType::class, [
                'label' => 'Rechercher un film',
                'required' => false,
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Année',
                'required' => false,
                'attr' => ['min' => 1900, 'max' => 2024],
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'required' => false,
                'choices' => $this->getGenreChoices(),
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Rechercher'
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
            'movies' => $movies,
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