<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\ApiResource\MovieApi;
use App\Service\TmdbService;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class MovieApiProvider implements ProviderInterface
{
    public function __construct(
        private TmdbService $tmdbService,
        private RequestStack $requestStack
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $this->requestStack->getCurrentRequest();

        if (isset($uriVariables['id'])) {
            // Get single movie
            $movie = $this->tmdbService->getMovieDetails($uriVariables['id']);
            if (!$movie) {
                throw new NotFoundHttpException('Film non trouvé');
            }
            return $this->mapMovieToApi($movie);
        }

        // Get collection of movies
        $query = $request->query->get('query');
        $year = $request->query->get('year');
        $genre = $request->query->get('genre');

        $filters = [];
        
        // Ajouter les filtres seulement s'ils sont présents
        if ($year) {
            $filters['primary_release_year'] = $year;
        }
        if ($genre) {
            $filters['with_genres'] = $genre;
        }

        try {
            // Utiliser searchMovies avec le terme de recherche et les filtres
            $movies = $this->tmdbService->searchMovies($query, null, $filters);
            return array_map([$this, 'mapMovieToApi'], $movies);
        } catch (\Exception $e) {
            return [];
        }
    }

    private function mapMovieToApi($movie): MovieApi
    {
        if (!$movie) {
            return new MovieApi();
        }

        $movieApi = new MovieApi();
        
        try {
            $movieApi->id = method_exists($movie, 'getTmdbId') ? $movie->getTmdbId() : null;
            $movieApi->title = method_exists($movie, 'getTitle') ? $movie->getTitle() : null;
            $movieApi->overview = method_exists($movie, 'getOverview') ? $movie->getOverview() : null;
            $movieApi->posterPath = method_exists($movie, 'getPosterPath') ? $movie->getPosterPath() : null;
            $movieApi->voteAverage = method_exists($movie, 'getVoteAverage') ? $movie->getVoteAverage() : null;
            $movieApi->releaseDate = method_exists($movie, 'getReleaseDate') ? $movie->getReleaseDate()?->format('Y-m-d') : null;
            $movieApi->genres = method_exists($movie, 'getGenres') ? $movie->getGenres() : [];
        } catch (\Exception $e) {
            // Log error if needed
        }

        return $movieApi;
    }
}
