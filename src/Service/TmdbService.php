<?php

namespace App\Service;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;

/**
 * Service for interacting with The Movie Database (TMDB) API
 * Handles movie data retrieval, caching, and database operations
 */
class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private string $apiKey;
    private string $accessToken;
    /** @var array Cache for genre mappings to avoid repeated API calls */
    private array $genresCache = [];

    /**
     * Initialize TMDB service with required dependencies
     *
     * @param HttpClientInterface $httpClient HTTP client for API requests
     * @param EntityManagerInterface $entityManager Doctrine entity manager for database operations
     * @param MovieRepository $movieRepository Repository for movie entity operations
     * @param string $tmdbApiKey API key for TMDB authentication
     * @param string $tmdbAccessToken Access token for TMDB API v4
     */
    public function __construct(
        private HttpClientInterface $httpClient,
        private EntityManagerInterface $entityManager,
        private MovieRepository $movieRepository,
        string $tmdbApiKey,
        string $tmdbAccessToken
    ) {
        $this->apiKey = $tmdbApiKey;
        $this->accessToken = $tmdbAccessToken;
    }

    /**
     * Retrieve detailed movie information by TMDB ID
     * First checks local database, then falls back to TMDB API
     *
     * @param int $id TMDB movie ID
     * @return Movie|null Returns Movie entity or null if not found
     */
    public function getMovieDetails(int $id): ?Movie
    {
        // First check local database for cached movie
        $movie = $this->movieRepository->findOneBy(['tmdbId' => $id]);
        
        if ($movie) {
            return $movie;
        }

        // If not found locally, fetch from TMDB API
        $response = $this->httpClient->request('GET', self::BASE_URL . '/movie/' . $id, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'accept' => 'application/json',
            ],
            'query' => [
                'language' => 'fr-FR',
            ],
        ]);

        $movieData = $response->toArray();
        return $this->createOrUpdateMovieFromData($movieData);
    }

    /**
     * Search for movies using TMDB API with optional filters
     * If query is null, falls back to discover endpoint
     * 
     * @param string|null $query Search term for movie titles
     * @param string|null $region Filter by region (ISO 3166-1 country code)
     * @param array $additionalFilters Additional TMDB API filters
     * @return array Array of Movie entities
     */
    public function searchMovies(string $query = null, ?string $region = null, array $additionalFilters = []): array
    {
        if ($query) {
            // Use search/movie endpoint when query is provided
            $response = $this->httpClient->request('GET', self::BASE_URL . '/search/movie', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken,
                    'accept' => 'application/json',
                ],
                'query' => array_merge([
                    'query' => $query,
                    'region' => $region,
                    'include_adult' => 'false',
                    'language' => 'fr-FR',
                ], $additionalFilters)
            ]);
        } else {
            // Fall back to discover endpoint for browsing
            return $this->discoverMovies($additionalFilters);
        }

        // Process API response
        $data = $response->toArray();
        $movies = [];

        // Convert each movie data to Movie entity
        foreach ($data['results'] as $movieData) {
            $movie = $this->createOrUpdateMovieFromData($movieData);
            $movies[] = $movie;
        }

        return $movies;
    }

    /**
     * Discover movies using TMDB API with filters
     * Used for browsing movies without specific search term
     * 
     * @param array $filters TMDB API filters (genres, year, sort, etc.)
     * @return array Array of Movie entities
     */
    public function discoverMovies(array $filters = []): array
    {
        // Handle genre filtering
        if (isset($filters['with_genres'])) {
            // Process genre IDs for API request
            $genreIds = $filters['with_genres'];
            if (!is_array($genreIds)) {
                $genreIds = [$genreIds];
            }
            $filters['with_genres'] = implode(',', $genreIds);
        }

        // Make API request to discover endpoint
        $response = $this->httpClient->request('GET', self::BASE_URL . '/discover/movie', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'accept' => 'application/json',
            ],
            'query' => array_merge([
                'include_adult' => 'false',
                'language' => 'fr-FR',
                'sort_by' => 'popularity.desc',
            ], $filters)
        ]);

        // Process API response
        $data = $response->toArray();
        $movies = [];

        // Convert each movie data to Movie entity
        foreach ($data['results'] as $movieData) {
            $movie = $this->createOrUpdateMovieFromData($movieData);
            $movies[] = $movie;
        }

        return $movies;
    }

    public function getGenres(): array
    {
        $response = $this->httpClient->request('GET', self::BASE_URL . '/genre/movie/list', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'accept' => 'application/json',
            ],
            'query' => [
                'language' => 'fr-FR',
            ],
        ]);

        $data = $response->toArray();
        return $data['genres'];
    }

    public function getGenreNameById(int $genreId): ?string
    {
        // Initialize genres cache if empty
        if (empty($this->genresCache)) {
            $genres = $this->getGenres();
            foreach ($genres as $genre) {
                $this->genresCache[$genre['id']] = $genre['name'];
            }
        }

        return $this->genresCache[$genreId] ?? null;
    }

    public function getGenreNamesFromIds(array $genreIds): array
    {
        $genreNames = [];
        foreach ($genreIds as $genreId) {
            $name = $this->getGenreNameById($genreId);
            if ($name) {
                $genreNames[] = $name;
            }
        }
        return $genreNames;
    }

    private function createOrUpdateMovieFromData(array $movieData): Movie
    {
        // Check if movie already exists in database
        $movie = $this->movieRepository->findOneBy(['tmdbId' => $movieData['id']]);

        if (!$movie) {
            $movie = new Movie();
            $movie->setTmdbId($movieData['id']);
        }

        $movie->setTitle($movieData['title']);
        $movie->setOverview($movieData['overview']);
        $movie->setVoteAverage($movieData['vote_average']);
        $movie->setVoteCount($movieData['vote_count']);
        $movie->setPosterPath($movieData['poster_path']);
        $movie->setBackdropPath($movieData['backdrop_path'] ?? null);
        
        if (isset($movieData['release_date'])) {
            $movie->setReleaseDate(new DateTime($movieData['release_date']));
        }
        
        if (isset($movieData['genre_ids'])) {
            $movie->setGenres($movieData['genre_ids']);
        } elseif (isset($movieData['genres'])) {
            $movie->setGenres(array_map(fn($genre) => $genre['id'], $movieData['genres']));
        }

        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        return $movie;
    }
}