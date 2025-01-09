<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Repository\MovieRepository;
use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class TmdbApiService
{
    private const API_URL = 'https://api.themoviedb.org/3';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        private HttpClientInterface $httpClient,
        private CacheInterface $cache,
        private string $apiKey,
        private MovieRepository $movieRepository,
        private EntityManagerInterface $entityManager,
        private LoggerInterface $logger,
        private FlashBagService $flashBag
    ) {}

    public function searchMovies(string $query, int $page = 1, array $filters = []): array
    {
        try {
            $this->flashBag->addFlash('debug', 'Searching movies with filters: ' . json_encode($filters, JSON_PRETTY_PRINT));

            // First try to find movies in local database
            $localResults = $this->movieRepository->findByFormFilters($filters);
            $this->flashBag->addFlash('debug', 'Local results count: ' . count($localResults));

            if (!empty($localResults)) {
                $result = [
                    'results' => $localResults,
                    'page' => 1,
                    'total_pages' => 1,
                    'total_results' => count($localResults),
                    'source' => 'local'
                ];
                $this->flashBag->addFlash('debug', 'Local database results: ' . json_encode($result, JSON_PRETTY_PRINT));
                return $result;
            }

            // If no local results, search TMDB API
            $response = $this->httpClient->request('GET', self::API_URL . '/search/movie', [
                'query' => array_merge([
                    'api_key' => $this->apiKey,
                    'query' => $query,
                    'page' => $page
                ], $filters)
            ]);

            $data = $response->toArray();
            $this->flashBag->addFlash('debug', 'TMDB API results: ' . json_encode($data, JSON_PRETTY_PRINT));

            // Save TMDB results to local database
            foreach ($data['results'] as $movieData) {
                $this->saveMovieToDatabase($movieData);
            }

            return $data;
        } catch (\Exception $e) {
            $this->logger->error('Error in searchMovies:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function getMovieDetails(int $movieId): array
    {
        $cacheKey = sprintf('tmdb_movie_%d', $movieId);

        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/movie/' . $movieId, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json'
                ],
                'query' => [
                    'language' => 'fr-FR',
                    'append_to_response' => 'credits,keywords,videos'
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to fetch movie details');
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching movie details:', [
                'id' => $movieId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPopularMovies(int $page = 1): array
    {
        try {
            $response = $this->httpClient->request('GET', self::API_URL . '/movie/popular', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->apiKey,
                    'Accept' => 'application/json'
                ],
                'query' => [
                    'language' => 'fr-FR',
                    'page' => $page
                ]
            ]);

            if ($response->getStatusCode() !== 200) {
                throw new \RuntimeException('Failed to fetch popular movies');
            }

            return $response->toArray();
        } catch (\Exception $e) {
            $this->logger->error('Error fetching popular movies:', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}