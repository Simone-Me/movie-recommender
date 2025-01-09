<?php

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use App\State\MovieApiProvider;

#[ApiResource(
    operations: [
        new Get(
            uriTemplate: '/movies/{id}',
            provider: MovieApiProvider::class
        ),
        new GetCollection(
            uriTemplate: '/movies',
            provider: MovieApiProvider::class
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
