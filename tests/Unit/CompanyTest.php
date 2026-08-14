<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Company;
use PHPUnit\Framework\TestCase;

final class CompanyTest extends TestCase
{
    public function testItKeepsADisplayNameAndCreatesAStableIdentity(): void
    {
        $company = new Company('  Acme   KFT. ');

        self::assertSame('Acme KFT.', $company->getName());
        self::assertSame('acme kft.', $company->getNormalizedName());
    }
}
