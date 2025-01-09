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

        // For new movies, ensure ID is set and unique
        if (!$data->getId()) {
            throw new BadRequestHttpException('Movie ID (TMDB ID) is required');
        }

        $existing = $this->entityManager->getRepository(Movie::class)->find($data->getId());
        if ($existing) {
            throw new BadRequestHttpException('Movie with this ID already exists');
        }

        $this->entityManager->persist($data);
        $this->entityManager->flush();

        return $data;
    }
}
