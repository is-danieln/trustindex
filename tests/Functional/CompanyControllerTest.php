<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Review;
use App\Repository\CompanyRepository;
use App\Service\CompanyResolver;
use App\Tests\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class CompanyControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    public function testVisitorCanOpenACompanyPage(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->createDatabaseSchema($entityManager);
        $companyResolver = self::getContainer()->get(CompanyResolver::class);

        $this->persistReview($entityManager, $companyResolver, 'Acme Kft.', 5, 'Kiváló ügyfélélmény.');
        $this->persistReview($entityManager, $companyResolver, ' ACME   KFT. ', 3, 'Összességében megfelelő.');
        $entityManager->flush();
        $company = self::getContainer()->get(CompanyRepository::class)->findByNormalizedName('acme kft.');
        self::assertNotNull($company);

        $client->request('GET', '/companies/'.$company->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Acme Kft.');
        self::assertSelectorTextContains('.company-detail-summary', '2 beérkezett vélemény alapján');
        self::assertSelectorTextContains('.company-score', '4,00');
        self::assertSelectorCount(2, '.review-card');
    }

    public function testUnknownCompanyReturnsNotFound(): void
    {
        $client = self::createClient();
        $this->createDatabaseSchema(self::getContainer()->get(EntityManagerInterface::class));

        $client->request('GET', '/companies/999999');

        self::assertResponseStatusCodeSame(404);
    }

    private function persistReview(
        EntityManagerInterface $entityManager,
        CompanyResolver $companyResolver,
        string $companyName,
        int $rating,
        string $reviewText,
    ): void {
        $review = (new Review())
            ->setCompany($companyResolver->resolve($companyName))
            ->setRating($rating)
            ->setReviewText($reviewText)
            ->setAuthorEmail('teszt@example.com');

        $entityManager->persist($review);
    }
}
