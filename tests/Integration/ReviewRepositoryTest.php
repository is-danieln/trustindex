<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use App\Service\CompanyResolver;
use App\Tests\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class ReviewRepositoryTest extends KernelTestCase
{
    use DatabaseTestTrait;

    private EntityManagerInterface $entityManager;
    private ReviewRepository $repository;
    private CompanyResolver $companyResolver;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->createDatabaseSchema($this->entityManager);
        $this->repository = self::getContainer()->get(ReviewRepository::class);
        $this->companyResolver = self::getContainer()->get(CompanyResolver::class);
    }

    public function testCompanyStatisticsAreAveragedAndSortedDescending(): void
    {
        $this->persistReview('Alfa Kft.', 3);
        $this->persistReview('Alfa Kft.', 5);
        $this->persistReview('Béta Zrt.', 5);
        $this->persistReview('Gamma Bt.', 2);
        $this->entityManager->flush();

        $statistics = $this->repository->findCompanyStatistics();

        self::assertSame(['Béta Zrt.', 'Alfa Kft.', 'Gamma Bt.'], array_column($statistics, 'companyName'));
        self::assertSame(5.0, $statistics[0]->averageRating);
        self::assertSame(4.0, $statistics[1]->averageRating);
        self::assertSame(2, $statistics[1]->reviewCount);
        self::assertSame(50.0, $statistics[1]->percentageFor(5));
    }

    public function testCompanyNameVariantsAreAggregatedTogether(): void
    {
        $this->persistReview('  Acme   Kft. ', 5);
        $this->persistReview('ACME KFT.', 3);
        $this->persistReview('Másik Cég', 4);
        $this->entityManager->flush();

        $statistics = $this->repository->findCompanyStatistics();

        self::assertCount(2, $statistics);
        self::assertSame('acme kft.', $statistics[0]->companyKey);
        self::assertSame(2, $statistics[0]->reviewCount);
        self::assertSame(4.0, $statistics[0]->averageRating);
    }

    public function testLatestReviewsCanBePaginated(): void
    {
        foreach (range(1, 12) as $ratingIndex) {
            $this->persistReview('Lapozható Kft.', ($ratingIndex % 5) + 1);
        }
        $this->entityManager->flush();

        $page = $this->repository->paginateLatest('lapozható', 2, 10);

        self::assertSame(12, $page->totalItems);
        self::assertCount(2, $page->items);
        self::assertSame(2, $page->currentPage);
    }

    public function testSearchTreatsLikeWildcardsAsLiteralCharacters(): void
    {
        $this->persistReview('100% Valódi Kft.', 5);
        $this->persistReview('1000 Valódi Kft.', 4);
        $this->entityManager->flush();

        $reviews = $this->repository->findLatest('100%');

        self::assertCount(1, $reviews);
        self::assertSame('100% Valódi Kft.', $reviews[0]->getCompanyName());
    }

    private function persistReview(string $companyName, int $rating): void
    {
        $review = (new Review())
            ->setCompany($this->companyResolver->resolve($companyName))
            ->setRating($rating)
            ->setReviewText('Korrekt és részletes ügyfélélmény.')
            ->setAuthorEmail('teszt@example.com');

        $this->entityManager->persist($review);
    }
}
