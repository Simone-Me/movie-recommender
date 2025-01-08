<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class TmdbService
{
    private const API_BASE_URL = 'https://api.themoviedb.org/3';
    private $client;
    private $accessToken;
    private $logger;

    public function __construct(HttpClientInterface $client, string $accessToken, LoggerInterface $logger)
    {
        $this->client = $client;
        $this->accessToken = $accessToken;
        $this->logger = $logger;
    }

    /**
     * @throws \RuntimeException
     */
    private function request(string $endpoint, array $parameters = []): array
    {
        try {
            $response = $this->client->request('GET', self::API_BASE_URL . $endpoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'Accept' => 'application/json',
                ],
                'query' => $parameters,
            ]);

            if ($response->getStatusCode() === 401) {
                throw new \RuntimeException('Invalid TMDB API token');
            }

            if ($response->getStatusCode() === 404) {
                throw new \RuntimeException('Resource not found on TMDB');
            }

            if ($response->getStatusCode() >= 400) {
                throw new \RuntimeException('TMDB API error: ' . $response->getContent(false));
            }

            return $response->toArray();
        } catch (TransportExceptionInterface $e) {
            throw new \RuntimeException('Network error while contacting TMDB: ' . $e->getMessage());
        } catch (HttpExceptionInterface $e) {
            throw new \RuntimeException('HTTP error while contacting TMDB: ' . $e->getMessage());
        } catch (\Exception $e) {
            throw new \RuntimeException('Error processing TMDB response: ' . $e->getMessage());
        }
    }

    public function discoverMoviesWithFilters(array $filters): array
    {
        try {
            // Base parameters as per TMDB API
            $apiParams = [
                'language' => 'en-US',
                'include_adult' => false,
                'include_video' => false,
                'page' => 1,
                'sort_by' => 'popularity.desc'
            ];

            // Map country code to region parameter
            if (!empty($filters['region'])) {
                $apiParams['region'] = strtoupper($filters['region']);  // TMDB expects uppercase ISO 3166-1 country codes
            }

            // Map genre using TMDB genre IDs
            if (!empty($filters['genre'])) {
                $genreMapping = [
                    'adventure' => 12,  // Adventure
                    'comedy' => 35,     // Comedy
                    'family' => 10751,  // Family
                    'action' => 28      // Action
                ];
                if (isset($genreMapping[$filters['genre']])) {
                    $apiParams['with_genres'] = $genreMapping[$filters['genre']];
                }
            }

            // Map year to primary_release_year
            if (!empty($filters['year'])) {
                $apiParams['primary_release_year'] = $filters['year'];
            }

            // Map sorting parameters
            if (!empty($filters['sortBy'])) {
                $sortMapping = [
                    'voteAverage' => 'vote_average',
                    'popularity' => 'popularity',
                    'releaseDate' => 'release_date',
                    'revenue' => 'revenue',
                    'voteCount' => 'vote_count'
                ];
                if (isset($sortMapping[$filters['sortBy']])) {
                    $apiParams['sort_by'] = $sortMapping[$filters['sortBy']] . '.desc';
                }
            }

            $this->logger->debug('TMDB API parameters', $apiParams);
            $response = $this->request('/discover/movie', $apiParams);
            
            if (!isset($response['results']) || empty($response['results'])) {
                return [];
            }

            // Get full details for each movie
            $movies = [];
            foreach ($response['results'] as $movie) {
                try {
                    $fullDetails = $this->getMovieDetails($movie['id']);
                    if ($fullDetails) {
                        // Merge basic movie data with full details
                        $movies[] = array_merge($movie, $fullDetails);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to get details for movie ' . $movie['id'] . ': ' . $e->getMessage());
                    // Add the basic movie data even if full details fail
                    $movies[] = $movie;
                }
            }

            // If limit is set, trim results
            if (!empty($filters['limit']) && !empty($movies)) {
                return array_slice($movies, 0, $filters['limit']);
            }

            return $movies;
        } catch (\Exception $e) {
            $this->logger->error('Error in discoverMoviesWithFilters: ' . $e->getMessage());
            return [];
        }
    }

    public function searchMovies(string $query)
    {
        try {
            return $this->request('/search/movie', ['query' => $query]);
        } catch (\Exception $e) {
            error_log('Error searching movies: ' . $e->getMessage());
            return [];
        }
    }

    public function getMovieDetails(int $movieId): array
    {
        try {
            return $this->request('/movie/' . $movieId, [
                'language' => 'en-US',
                'append_to_response' => 'credits,keywords,videos'
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching movie details for ID ' . $movieId . ': ' . $e->getMessage());
            return [];
        }
    }

    public function getGenres(): array
    {
        try {
            return $this->request('/genre/movie/list');
        } catch (\Exception $e) {
            error_log('Error fetching genres: ' . $e->getMessage());
            return ['genres' => []];
        }
    }

    public function discoverMovies(array $filters = [])
    {
        try {
            return $this->request('/discover/movie', $filters);
        } catch (\Exception $e) {
            error_log('Error discovering movies: ' . $e->getMessage());
            return [];
        }
    }
}