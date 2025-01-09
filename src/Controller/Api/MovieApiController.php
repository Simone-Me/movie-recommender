<?php

namespace App\Controller\Api;

use App\Service\TmdbApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class MovieApiController extends AbstractController
{
    private TmdbApiService $tmdbApiService;

    public function __construct(TmdbApiService $tmdbApiService)
    {
        $this->tmdbApiService = $tmdbApiService;
    }

    #[Route('/api/movies/search', name: 'movies_search', methods: ['GET'])]
    public function search(Request $request): JsonResponse
    {
        $query = $request->query->get('query');
        $page = $request->query->get('page', 1);
        
        // Get filters from request
        $filters = [
            'genre' => $request->query->get('genre'),
            'region' => $request->query->get('region'),
            'year' => $request->query->get('year'),
            'sortBy' => $request->query->get('sortBy'),
        ];

        // Remove empty filters
        $filters = array_filter($filters);

        if (!$query && empty($filters)) {
            return $this->json(['error' => 'Query or filters are required'], 400);
        }

        $results = $this->tmdbApiService->searchMovies($query, $page, $filters);
        return $this->json($results);
    }

    #[Route('/movies/{id}', name: 'movie_details', methods: ['GET'])]
    public function details(int $id): JsonResponse
    {
        try {
            $movie = $this->tmdbApiService->getMovieDetails($id);
            return $this->json($movie);
        } catch (\Exception $e) {
            return $this->json(['error' => 'Movie not found'], 404);
        }
    }

    #[Route('/movies/popular', name: 'movies_popular', methods: ['GET'])]
    public function popular(Request $request): JsonResponse
    {
        $page = $request->query->get('page', 1);
        $results = $this->tmdbApiService->getPopularMovies($page);
        return $this->json($results);
    }
}
