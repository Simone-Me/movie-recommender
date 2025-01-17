<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Movie>
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function findByTitlePattern(string $query): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    public function findByGenres(array $genreIds): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.genres IS NOT NULL')
            ->andWhere('m.genres != :empty')
            ->setParameter('empty', '[]')
            ->getQuery()
            ->getResult();
    }

    public function findMovieById(int $id): ?Movie
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function searchMovies(?string $title, ?int $releaseDate, ?String $genreIds): array
    {
        $queryBuilder = $this->createQueryBuilder('m');

        if ($title) {
            $queryBuilder->andWhere('m.title LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }

        if ($releaseDate) {
            $queryBuilder->andWhere('YEAR(m.releaseDate) = :releaseDate')
                ->setParameter('releaseDate', $releaseDate);
        }

        if (!empty($genreIds)) {
            $queryBuilder->andWhere('m.genres IN (:genres)')
                ->setParameter('genres', $genreIds);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    //    /**
    //     * @return Movie[] Returns an array of Movie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('m.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Movie
    //    {
    //        return $this->createQueryBuilder('m')
    //            ->andWhere('m.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}