<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TmdbService
{
    private const API_BASE_URL = 'https://api.themoviedb.org/3';
    private $client;
    private $accessToken;

    public function __construct(HttpClientInterface $client, string $accessToken)
    {
        $this->client = $client;
        $this->accessToken = $accessToken;
    }

    private function request(string $endpoint, array $parameters = [])
    {
        $response = $this->client->request('GET', self::API_BASE_URL . $endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->accessToken,
                'Accept' => 'application/json',
            ],
            'query' => $parameters,
        ]);

        return $response->toArray();
    }

    public function searchMovies(string $query)
    {
        return $this->request('/search/movie', ['query' => $query]);
    }

    public function getMovieDetails(int $movieId)
    {
        return $this->request('/movie/' . $movieId);
    }

    public function getGenres()
    {
        return $this->request('/genre/movie/list');
    }

    public function discoverMovies(array $filters = [])
    {
        return $this->request('/discover/movie', $filters);
    }
}