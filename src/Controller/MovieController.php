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
    public function details(int $id): Response
    {
        $movie = $this->tmdbService->getMovieDetails($id);

        return $this->render('movie/details.html.twig', [
            'movie' => $movie,
            'tmdbService' => $this->tmdbService,
        ]);
    }
}