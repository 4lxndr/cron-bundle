<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Domain\CronJobCounter;
use Shapecode\Bundle\CronBundle\Domain\CronJobMetadata;
use Symfony\Component\Console\Command\Command;

#[CoversClass(CronJobCounter::class)]
class CronJobCounterTest extends TestCase
{
    private CronJobCounter $counter;

    protected function setUp(): void
    {
        $this->counter = new CronJobCounter();
    }

    public function testValueReturnsZeroForNewCommand(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test');

        $metadata = CronJobMetadata::createByCommand('@daily', $command);

        self::assertSame(0, $this->counter->value($metadata));
    }

    public function testIncreaseIncrementsCounter(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test');

        $metadata = CronJobMetadata::createByCommand('@daily', $command);

        $this->counter->increase($metadata);

        self::assertSame(1, $this->counter->value($metadata));
    }

    public function testIncreaseMultipleTimes(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test');

        $metadata = CronJobMetadata::createByCommand('@daily', $command);

        $this->counter->increase($metadata);
        $this->counter->increase($metadata);
        $this->counter->increase($metadata);

        self::assertSame(3, $this->counter->value($metadata));
    }

    public function testDifferentCommandsHaveSeparateCounters(): void
    {
        $command1 = self::createStub(Command::class);
        $command1->method('getName')->willReturn('test:command1');
        $command1->method('getDescription')->willReturn('Test 1');

        $command2 = self::createStub(Command::class);
        $command2->method('getName')->willReturn('test:command2');
        $command2->method('getDescription')->willReturn('Test 2');

        $metadata1 = CronJobMetadata::createByCommand('@daily', $command1);
        $metadata2 = CronJobMetadata::createByCommand('@hourly', $command2);

        $this->counter->increase($metadata1);
        $this->counter->increase($metadata1);
        $this->counter->increase($metadata2);

        self::assertSame(2, $this->counter->value($metadata1));
        self::assertSame(1, $this->counter->value($metadata2));
    }
}
