<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Review;
use App\Tests\DatabaseTestTrait;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ReviewControllerTest extends WebTestCase
{
    use DatabaseTestTrait;

    public function testVisitorCanSubmitAReview(): void
    {
        $client = self::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->createDatabaseSchema($entityManager);

        $crawler = $client->request('GET', '/reviews/new');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Vélemény elküldése')->form([
            'review[companyName]' => 'Mintacég Kft.',
            'review[rating]' => '5',
            'review[reviewText]' => 'Gyorsan és segítőkészen kezelték a kérésemet.',
            'review[authorEmail]' => 'ugyfel@example.com',
        ]);
        $client->submit($form);

        self::assertResponseRedirects('/reviews/1');
        $client->followRedirect();
        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('.flash', 'Köszönjük a véleményed!');
        self::assertSelectorTextContains('h1', 'Mintacég Kft.');
        self::assertSelectorTextNotContains('body', 'ugyfel@example.com');

        $review = self::getContainer()
            ->get(EntityManagerInterface::class)
            ->getRepository(Review::class)
            ->findOneBy([]);
        self::assertNotNull($review);
        self::assertSame('Mintacég Kft.', $review->getCompanyName());
        self::assertSame('mintacég kft.', $review->getCompany()?->getNormalizedName());
        self::assertSame(5, $review->getRating());
    }

    public function testInvalidReviewShowsValidationErrors(): void
    {
        $client = self::createClient();
        $this->createDatabaseSchema(self::getContainer()->get(EntityManagerInterface::class));

        $crawler = $client->request('GET', '/reviews/new');
        $client->submit($crawler->selectButton('Vélemény elküldése')->form([
            'review[companyName]' => '',
            'review[rating]' => '',
            'review[reviewText]' => '',
            'review[authorEmail]' => 'hibas-email',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorTextContains('body', 'Add meg a cég nevét.');
        self::assertSelectorTextContains('body', 'Válassz értékelést.');
        self::assertSelectorTextContains('body', 'Írd le a tapasztalatodat.');
        self::assertSelectorTextContains('body', 'Adj meg egy érvényes e-mail-címet.');
    }
}
