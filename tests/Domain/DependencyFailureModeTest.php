<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;

#[CoversClass(DependencyFailureMode::class)]
class DependencyFailureModeTest extends TestCase
{
    public function testSkipCase(): void
    {
        self::assertSame('skip', DependencyFailureMode::SKIP->value);
    }

    public function testRunCase(): void
    {
        self::assertSame('run', DependencyFailureMode::RUN->value);
    }

    public function testDisableCase(): void
    {
        self::assertSame('disable', DependencyFailureMode::DISABLE->value);
    }

    public function testFromString(): void
    {
        self::assertSame(DependencyFailureMode::SKIP, DependencyFailureMode::from('skip'));
        self::assertSame(DependencyFailureMode::RUN, DependencyFailureMode::from('run'));
        self::assertSame(DependencyFailureMode::DISABLE, DependencyFailureMode::from('disable'));
    }

    public function testAllEnumCasesExist(): void
    {
        $cases = DependencyFailureMode::cases();

        self::assertCount(3, $cases);
        self::assertContains(DependencyFailureMode::SKIP, $cases);
        self::assertContains(DependencyFailureMode::RUN, $cases);
        self::assertContains(DependencyFailureMode::DISABLE, $cases);
    }
}
