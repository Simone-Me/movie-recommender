<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\MovieApi;
use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;

class MovieApiProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): MovieApi
    {
        $movie = new Movie();
        $movie->setTitle($data->title);
        $movie->setOverview($data->overview);
        $movie->setPosterPath($data->posterPath);
        $movie->setVoteAverage($data->voteAverage);
        $movie->setReleaseDate(new \DateTime($data->releaseDate));
        $movie->setGenres($data->genres);

        $this->entityManager->persist($movie);
        $this->entityManager->flush();

        // Map back to API Resource
        $data->id = $movie->getId();
        
        return $data;
    }
}