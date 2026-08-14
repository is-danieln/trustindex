<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Domain\CompanyNameNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class CompanyNameNormalizerTest extends TestCase
{
    /**
     * @return iterable<string, array{string, string}>
     */
    public static function nameProvider(): iterable
    {
        yield 'case and surrounding whitespace' => ['  TrustIndex  ', 'trustindex'];
        yield 'repeated whitespace' => ["Minta\t  Cég Kft.", 'minta cég kft.'];
        yield 'Hungarian characters' => ['ÁRVÍZTŰRŐ Zrt.', 'árvíztűrő zrt.'];
    }

    #[DataProvider('nameProvider')]
    public function testItCreatesAStableCompanyKey(string $name, string $expected): void
    {
        self::assertSame($expected, CompanyNameNormalizer::key($name));
    }

    public function testItEscapesLikeWildcards(): void
    {
        self::assertSame('%50!%!! !_off%', CompanyNameNormalizer::searchPattern('50%! _off'));
    }
}
