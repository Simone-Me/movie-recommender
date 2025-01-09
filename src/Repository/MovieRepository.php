<?php

namespace App\Repository;

use App\Entity\Movie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\QueryException;

/**
 * @extends ServiceEntityRepository<Movie>
 *
 * @method Movie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Movie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Movie[]    findAll()
 * @method Movie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MovieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Movie::class);
    }

    public function save(Movie $entity, bool $flush = false): void
    {
        try {
            $this->getEntityManager()->persist($entity);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        } catch (\Exception $e) {
            error_log('Error saving movie: ' . $e->getMessage());
            throw $e;
        }
    }

    public function remove(Movie $entity, bool $flush = false): void
    {
        try {
            $this->getEntityManager()->remove($entity);

            if ($flush) {
                $this->getEntityManager()->flush();
            }
        } catch (\Exception $e) {
            error_log('Error removing movie: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Find movies by genre
     */
    public function findByGenre(string $genre, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.genres LIKE :genre')
            ->setParameter('genre', '%"' . $genre . '"%')
            ->orderBy('m.voteAverage', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find movies by production country
     */
    public function findByCountry(string $country, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.productionCountries LIKE :country')
            ->setParameter('country', '%' . $country . '%')
            ->orderBy('m.voteAverage', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find movies by year
     */
    public function findByYear(int $year, int $limit = 10): array
    {
        $startDate = new \DateTime($year . '-01-01');
        $endDate = new \DateTime($year . '-12-31');

        return $this->createQueryBuilder('m')
            ->andWhere('m.releaseDate BETWEEN :start AND :end')
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('m.voteAverage', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find movies with custom sorting
     */
    public function findWithSorting(string $sortBy = 'voteAverage', string $direction = 'DESC', int $limit = 10): array
    {
        $validSortFields = ['voteAverage', 'title', 'popularity', 'releaseDate', 'revenue', 'voteCount'];
        $sortField = in_array($sortBy, $validSortFields) ? $sortBy : 'voteAverage';

        return $this->createQueryBuilder('m')
            ->orderBy('m.' . $sortField, $direction)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find movies by language code
     */
    public function findByLanguageCode(string $languageCode, int $limit = 10): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.originalLanguage = :languageCode')
            ->setParameter('languageCode', strtolower($languageCode))
            ->orderBy('m.voteAverage', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find movies by combined filters from form
     */
    public function findByFormFilters(array $filters): array
    {
        // Debug input filters
        var_dump('Input filters:', $filters);

        $queryBuilder = $this->createQueryBuilder('m');

        if (!empty($filters['genre'])) {
            $queryBuilder
                ->andWhere('m.genre = :genre')
                ->setParameter('genre', $filters['genre']);
        }

        if (!empty($filters['year'])) {
            $queryBuilder
                ->andWhere('YEAR(m.releaseDate) = :year')
                ->setParameter('year', $filters['year']);
        }

        if (!empty($filters['region'])) {
            $queryBuilder
                ->andWhere('m.productionCountry = :region')
                ->setParameter('region', $filters['region']);
        }

        // Debug final query
        var_dump('Generated DQL:', $queryBuilder->getDQL());
        var_dump('Parameters:', $queryBuilder->getParameters()->toArray());

        $result = $queryBuilder->getQuery()->getResult();
        
        // Debug result count
        var_dump('Results count:', count($result));

        return $result;
    }
}