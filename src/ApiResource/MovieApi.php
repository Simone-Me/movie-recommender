<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post;
use App\State\MovieApiProvider;
use App\State\MovieApiProcessor;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/movies/{id}',
            provider: MovieApiProvider::class
        ),
        new GetCollection(
            uriTemplate: '/movies',
            provider: MovieApiProvider::class
        ),
        new Post(
            uriTemplate: '/movies/new',
            processor: MovieApiProcessor::class,
            validationContext: ['groups' => ['movie:write']]
        )
    ],
    provider: MovieApiProvider::class
)]
class MovieApi
{
    public ?int $id = null;
    public ?string $title = null;
    public ?string $overview = null;
    public ?string $posterPath = null;
    public ?float $voteAverage = null;
    public ?string $releaseDate = null;
    public ?array $genres = null;
}
