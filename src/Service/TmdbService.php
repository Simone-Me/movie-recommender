<?php

namespace App\Service;

use App\Entity\Movie;
use App\Repository\MovieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use DateTime;

class TmdbService
{
    private const BASE_URL = 'https://api.themoviedb.org/3';
    private string $apiKey;
    private string $accessToken;
    private array $genresCache = [];

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

    public function getMovieDetails(int $id): ?Movie
    {
        // Check if movie exists in database
        $movie = $this->movieRepository->findOneBy(['tmdbId' => $id]);
        
        if ($movie) {
            return $movie;
        }

        // If not in database, fetch from TMDB
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

    public function searchMovies(string $query = null, ?string $region = null, array $additionalFilters = []): array
    {
        if ($query) {
            // Si on a un terme de recherche, on utilise search/movie avec des filtres additionnels
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
            // Si on n'a pas de terme de recherche, on utilise discover/movie
            return $this->discoverMovies($additionalFilters);
        }

        $data = $response->toArray();
        $movies = [];

        foreach ($data['results'] as $movieData) {
            $movie = $this->createOrUpdateMovieFromData($movieData);
            $movies[] = $movie;
        }

        return $movies;
    }

    public function discoverMovies(array $filters = []): array
    {
        // If genre_ids are provided, use them
        if (isset($filters['with_genres'])) {
            if (is_array($filters['with_genres'])) {
                $filters['with_genres'] = implode(',', $filters['with_genres']);
            }
        }

        // Make request to TMDB
        $response = $this->httpClient->request('GET', self::BASE_URL . '/discover/movie', [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'accept' => 'application/json',
            ],
            'query' => array_merge([
                'include_adult' => 'false',
                'language' => 'fr-FR',
            ], $filters)
        ]);

        $data = $response->toArray();
        $movies = [];

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