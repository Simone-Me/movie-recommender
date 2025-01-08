<?php

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Movie;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class MovieStateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): Movie
    {
        if (!$data instanceof Movie) {
            throw new \InvalidArgumentException('Data is not an instance of Movie');
        }

        // For new movies, ensure TMDB ID is unique
        if (!$data->getId() && $data->getTmdbId()) {
            $existing = $this->entityManager->getRepository(Movie::class)->findOneBy(['tmdbId' => $data->getTmdbId()]);
            if ($existing) {
                throw new BadRequestHttpException('Movie with this TMDB ID already exists');
            }
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
