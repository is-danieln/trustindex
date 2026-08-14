<?php

declare(strict_types=1);

namespace App\ValueObject;

use App\Entity\Review;

final readonly class ReviewPage
{
    /**
     * @param list<Review> $items
     */
    public function __construct(
        public array $items,
        public int $currentPage,
        public int $perPage,
        public int $totalItems,
    ) {
    }

    public function totalPages(): int
    {
        return max(1, (int) ceil($this->totalItems / $this->perPage));
    }

    public function hasPreviousPage(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNextPage(): bool
    {
        return $this->currentPage < $this->totalPages();
    }
}
