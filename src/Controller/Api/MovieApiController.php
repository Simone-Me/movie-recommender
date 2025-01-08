<?php

namespace App\Controller\Api;

use App\Service\TmdbService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class MovieApiController extends AbstractController
{
    private $tmdbService;

    public function __construct(TmdbService $tmdbService)
    {
        $this->tmdbService = $tmdbService;
    }

    #[Route('/movies/search', name: 'search_movies', methods: ['GET'])]
    public function searchMovies(Request $request): JsonResponse
    {
        $query = $request->query->get('query');
        if (!$query) {
            return new JsonResponse(['error' => 'Query parameter is required'], 400);
        }

        $results = $this->tmdbService->searchMovies($query);
        return new JsonResponse($results);
    }

    #[Route('/movies/{id}', name: 'movie_details', methods: ['GET'])]
    public function getMovieDetails(int $id): JsonResponse
    {
        try {
            $movie = $this->tmdbService->getMovieDetails($id);
            return new JsonResponse($movie);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Movie not found'], 404);
        }
    }

    #[Route('/genres', name: 'movie_genres', methods: ['GET'])]
    public function getGenres(): JsonResponse
    {
        $genres = $this->tmdbService->getGenres();
        return new JsonResponse($genres);
    }

    #[Route('/movies/discover', name: 'discover_movies', methods: ['GET'])]
    public function discoverMovies(Request $request): JsonResponse
    {
        $filters = [
            'year' => $request->query->get('year'),
            'with_genres' => $request->query->get('genre'),
            'sort_by' => $request->query->get('sort_by', 'popularity.desc'),
            'page' => $request->query->get('page', 1)
        ];

        // Remove null values
        $filters = array_filter($filters);
        
        $results = $this->tmdbService->discoverMovies($filters);
        return new JsonResponse($results);
    }
}
