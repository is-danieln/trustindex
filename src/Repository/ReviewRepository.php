<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Review;
use App\ValueObject\CompanyStatistics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
final class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return list<Review>
     */
    public function findLatest(?string $companyQuery = null): array
    {
        $queryBuilder = $this->createQueryBuilder('review')
            ->orderBy('review.createdAt', 'DESC')
            ->addOrderBy('review.id', 'DESC');

        if (null !== $companyQuery && '' !== trim($companyQuery)) {
            $queryBuilder
                ->andWhere('LOWER(review.companyName) LIKE LOWER(:companyQuery)')
                ->setParameter('companyQuery', '%'.trim($companyQuery).'%');
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return list<CompanyStatistics>
     */
    public function findCompanyStatistics(?string $companyQuery = null): array
    {
        $queryBuilder = $this->createQueryBuilder('review')
            ->select('review.companyName AS companyName')
            ->addSelect('COUNT(review.id) AS reviewCount')
            ->addSelect('AVG(review.rating) AS averageRating')
            ->addSelect('SUM(CASE WHEN review.rating = 5 THEN 1 ELSE 0 END) AS fiveStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 4 THEN 1 ELSE 0 END) AS fourStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 3 THEN 1 ELSE 0 END) AS threeStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 2 THEN 1 ELSE 0 END) AS twoStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 1 THEN 1 ELSE 0 END) AS oneStarCount')
            ->groupBy('review.companyName')
            ->orderBy('averageRating', 'DESC')
            ->addOrderBy('review.companyName', 'ASC');

        if (null !== $companyQuery && '' !== trim($companyQuery)) {
            $queryBuilder
                ->andWhere('LOWER(review.companyName) LIKE LOWER(:companyQuery)')
                ->setParameter('companyQuery', '%'.trim($companyQuery).'%');
        }

        /** @var list<array<string, int|float|string>> $rows */
        $rows = $queryBuilder->getQuery()->getArrayResult();

        return array_map(CompanyStatistics::fromDatabaseRow(...), $rows);
    }
}
