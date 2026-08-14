<?php

declare(strict_types=1);

namespace App\Repository;

use App\Domain\CompanyNameNormalizer;
use App\Entity\Company;
use App\Entity\Review;
use App\ValueObject\CompanyStatistics;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
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
                ->innerJoin('review.company', 'company')
                ->andWhere("company.normalizedName LIKE :companyQuery ESCAPE '!'")
                ->setParameter('companyQuery', CompanyNameNormalizer::searchPattern($companyQuery));
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @return list<Review>
     */
    public function findLatestByCompany(Company $company): array
    {
        return $this->createQueryBuilder('review')
            ->andWhere('review.company = :company')
            ->setParameter('company', $company)
            ->orderBy('review.createdAt', 'DESC')
            ->addOrderBy('review.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<CompanyStatistics>
     */
    public function findCompanyStatistics(?string $companyQuery = null): array
    {
        $queryBuilder = $this->createCompanyStatisticsQueryBuilder()
            ->orderBy('averageRating', 'DESC')
            ->addOrderBy('companyName', 'ASC');

        if (null !== $companyQuery && '' !== trim($companyQuery)) {
            $queryBuilder
                ->andWhere("company.normalizedName LIKE :companyQuery ESCAPE '!'")
                ->setParameter('companyQuery', CompanyNameNormalizer::searchPattern($companyQuery));
        }

        /** @var list<array<string, int|float|string>> $rows */
        $rows = $queryBuilder->getQuery()->getArrayResult();

        return array_map(CompanyStatistics::fromDatabaseRow(...), $rows);
    }

    public function findCompanyStatisticsByCompany(Company $company): ?CompanyStatistics
    {
        /** @var array<string, int|float|string>|null $row */
        $row = $this->createCompanyStatisticsQueryBuilder()
            ->andWhere('review.company = :company')
            ->setParameter('company', $company)
            ->getQuery()
            ->getOneOrNullResult();

        return null === $row ? null : CompanyStatistics::fromDatabaseRow($row);
    }

    private function createCompanyStatisticsQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('review')
            ->innerJoin('review.company', 'company')
            ->select('company.id AS companyId')
            ->addSelect('company.name AS companyName')
            ->addSelect('company.normalizedName AS companyKey')
            ->addSelect('COUNT(review.id) AS reviewCount')
            ->addSelect('AVG(review.rating) AS averageRating')
            ->addSelect('SUM(CASE WHEN review.rating = 5 THEN 1 ELSE 0 END) AS fiveStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 4 THEN 1 ELSE 0 END) AS fourStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 3 THEN 1 ELSE 0 END) AS threeStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 2 THEN 1 ELSE 0 END) AS twoStarCount')
            ->addSelect('SUM(CASE WHEN review.rating = 1 THEN 1 ELSE 0 END) AS oneStarCount')
            ->groupBy('company.id')
            ->addGroupBy('company.name')
            ->addGroupBy('company.normalizedName');
    }
}
