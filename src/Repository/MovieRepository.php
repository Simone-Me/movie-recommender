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
        $qb = $this->createQueryBuilder('m')
            ->where('m.title LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('m.releaseDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    public function findByGenres(array $genreIds): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.genres IS NOT NULL')
            ->andWhere('m.genres != :empty')
            ->setParameter('empty', '[]');

        foreach ($genreIds as $index => $genreId) {
            $qb->andWhere('JSON_CONTAINS(m.genres, :genreId' . $index . ') = 1')
               ->setParameter('genreId' . $index, $genreId);
        }

        return $qb->orderBy('m.releaseDate', 'DESC')
                 ->getQuery()
                 ->getResult();
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
