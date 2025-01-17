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
    private $movieRepository;

    public function __construct(TmdbService $tmdbService, MovieRepository $movieRepository)
    {
        $this->tmdbService = $tmdbService;
        $this->movieRepository = $movieRepository;
    }

    #[Route('/', name: 'app_home')]
    public function index(Request $request): Response
    {
        $form = $this->createFormBuilder()
            ->add('query', TextType::class, [
                'label' => 'Rechercher un film',
                'required' => false,
                'attr' => ['id' => 'search_query'],
            ])
            ->add('year', IntegerType::class, [
                'label' => 'Année',
                'required' => false,
                'attr' => ['min' => 1900, 'max' => 2024, 'id' => 'search_year'],
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'Genre',
                'required' => false,
                'choices' => $this->getGenreChoices(),
                'attr' => ['id' => 'search_genre'],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Rechercher'
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $title = $data['query'];
            $releaseDate = $data['year'];
            $genres = '[' . $data['genre'] . ']';
            //$genres =  $data['genre'];

            //var_dump($data);

            if (empty($title) && empty($releaseDate) && empty($genres)) {
                $this->addFlash('error', 'Veuillez entrer au moins un critère de recherche (titre ou année ou genre).');
                $movies = [];
            } else {;
                $movies = $this->movieRepository->searchMovies($title, $releaseDate, $genres);
                //var_dump($movies);
            }
        } else {
            
            $movies = [];
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
    public function details(int $id): Response
    {
        $movie = $this->tmdbService->getMovieDetails($id);

        return $this->render('movie/details.html.twig', [
            'movie' => $movie,
            'tmdbService' => $this->tmdbService,
        ]);
    }
}