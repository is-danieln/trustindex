<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\ValueObject\ReviewPage;
use PHPUnit\Framework\TestCase;

final class ReviewPageTest extends TestCase
{
    public function testItCalculatesNavigationState(): void
    {
        $page = new ReviewPage(items: [], currentPage: 2, perPage: 10, totalItems: 25);

        self::assertSame(3, $page->totalPages());
        self::assertTrue($page->hasPreviousPage());
        self::assertTrue($page->hasNextPage());
    }

    public function testAnEmptyResultStillHasOnePage(): void
    {
        $page = new ReviewPage(items: [], currentPage: 1, perPage: 10, totalItems: 0);

        self::assertSame(1, $page->totalPages());
        self::assertFalse($page->hasPreviousPage());
        self::assertFalse($page->hasNextPage());
    }
}
