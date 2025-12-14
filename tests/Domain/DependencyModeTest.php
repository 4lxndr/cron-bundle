<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;

#[CoversClass(DependencyMode::class)]
class DependencyModeTest extends TestCase
{
    public function testAndCase(): void
    {
        self::assertSame('and', DependencyMode::AND->value);
    }

    public function testOrCase(): void
    {
        self::assertSame('or', DependencyMode::OR->value);
    }

    public function testFromString(): void
    {
        self::assertSame(DependencyMode::AND, DependencyMode::from('and'));
        self::assertSame(DependencyMode::OR, DependencyMode::from('or'));
    }

    public function testAllEnumCasesExist(): void
    {
        $cases = DependencyMode::cases();

        self::assertCount(2, $cases);
        self::assertContains(DependencyMode::AND, $cases);
        self::assertContains(DependencyMode::OR, $cases);
    }
}
