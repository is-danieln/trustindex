<?php

declare(strict_types=1);

namespace App\ValueObject;

final readonly class CompanyStatistics
{
    /**
     * @param array<int, int> $ratingCounts Number of reviews keyed by rating (1-5)
     */
    public function __construct(
        public string $companyName,
        public string $companyKey,
        public int $reviewCount,
        public float $averageRating,
        public array $ratingCounts,
    ) {
    }

    /**
     * Doctrine returns scalar aggregate values as numeric strings, so the conversion
     * belongs at the repository boundary instead of leaking into controllers or Twig.
     *
     * @param array<string, int|float|string> $row
     */
    public static function fromDatabaseRow(array $row): self
    {
        return new self(
            companyName: (string) $row['companyName'],
            companyKey: (string) $row['companyKey'],
            reviewCount: (int) $row['reviewCount'],
            averageRating: round((float) $row['averageRating'], 2),
            ratingCounts: [
                5 => (int) $row['fiveStarCount'],
                4 => (int) $row['fourStarCount'],
                3 => (int) $row['threeStarCount'],
                2 => (int) $row['twoStarCount'],
                1 => (int) $row['oneStarCount'],
            ],
        );
    }

    public function percentageFor(int $rating): float
    {
        if (0 === $this->reviewCount) {
            return 0.0;
        }

        return round(($this->ratingCounts[$rating] ?? 0) / $this->reviewCount * 100, 1);
    }
}
