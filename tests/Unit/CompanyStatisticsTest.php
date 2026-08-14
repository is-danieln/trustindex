<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\ValueObject\CompanyStatistics;
use PHPUnit\Framework\TestCase;

final class CompanyStatisticsTest extends TestCase
{
    public function testItNormalizesDatabaseAggregatesAndCalculatesDistribution(): void
    {
        $statistics = CompanyStatistics::fromDatabaseRow([
            'companyName' => 'Acme Kft.',
            'companyKey' => 'acme kft.',
            'reviewCount' => '4',
            'averageRating' => '4.25',
            'fiveStarCount' => '2',
            'fourStarCount' => '1',
            'threeStarCount' => '1',
            'twoStarCount' => '0',
            'oneStarCount' => '0',
        ]);

        self::assertSame('Acme Kft.', $statistics->companyName);
        self::assertSame('acme kft.', $statistics->companyKey);
        self::assertSame(4, $statistics->reviewCount);
        self::assertSame(4.25, $statistics->averageRating);
        self::assertSame([5 => 2, 4 => 1, 3 => 1, 2 => 0, 1 => 0], $statistics->ratingCounts);
        self::assertSame(50.0, $statistics->percentageFor(5));
        self::assertSame(25.0, $statistics->percentageFor(4));
    }
}
