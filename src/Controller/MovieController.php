<?php

namespace App\Controller;

use App\Service\TmdbService;
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
    public function index(Request $request): Response
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

            if (!empty($data['query'])) {
                $searchResults = $this->tmdbService->searchMovies($data['query']);
                $movies = $searchResults['results'] ?? [];
            } else {
                if (!empty($data['year'])) {
                    $filters['primary_release_year'] = $data['year'];
                }
                if (!empty($data['genre'])) {
                    $filters['with_genres'] = $data['genre'];
                }
                var_dump($filters);

                $discoverResults = $this->tmdbService->discoverMovies($filters);
                $movies = $discoverResults['results'] ?? [];
            }
        }

        return $this->render('movie/index.html.twig', [
            'form' => $form->createView(),
            'movies' => $movies,
        ]);
    }

    private function getGenreChoices(): array
    {
        $genres = $this->tmdbService->getGenres();
        $choices = [];

        foreach ($genres['genres'] ?? [] as $genre) {
            $choices[$genre['name']] = $genre['id'];
        }

        return $choices;
    }

    #[Route('/movie/{id}', name: 'movie_details')]
    public function details(int $id): Response
    {
        $movie = $this->tmdbService->getMovieDetails($id);

        return $this->render('movie/details.html.twig', [
            'movie' => $movie,
        ]);
    }
}