<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Domain;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Shapecode\Bundle\CronBundle\Domain\CronJobMetadata;
use Symfony\Component\Console\Command\Command;

#[CoversClass(CronJobMetadata::class)]
class CronJobMetadataTest extends TestCase
{
    public function testCreateByCommandWithAllParameters(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test description');

        $metadata = CronJobMetadata::createByCommand(
            '@daily',
            $command,
            '--option=value',
            3,
        );

        self::assertSame('@daily', $metadata->expression);
        self::assertSame('test:command', $metadata->command);
        self::assertSame('--option=value', $metadata->arguments);
        self::assertSame(3, $metadata->maxInstances);
        self::assertSame('Test description', $metadata->description);
    }

    public function testCreateByCommandWithMinimalParameters(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test description');

        $metadata = CronJobMetadata::createByCommand('@hourly', $command);

        self::assertSame('@hourly', $metadata->expression);
        self::assertSame('test:command', $metadata->command);
        self::assertNull($metadata->arguments);
        self::assertSame(1, $metadata->maxInstances);
        self::assertSame('Test description', $metadata->description);
    }

    public function testCreateByCommandStripsBackslashesFromExpression(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test');

        $metadata = CronJobMetadata::createByCommand('\\@daily\\', $command);

        self::assertSame('@daily', $metadata->expression);
    }

    public function testCreateByCommandThrowsExceptionWhenCommandHasNoName(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn(null);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('command has to have a name provided');
        $this->expectExceptionCode(1653426725688);

        CronJobMetadata::createByCommand('@daily', $command);
    }

    public function testReadonlyProperties(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');
        $command->method('getDescription')->willReturn('Test');

        $metadata = CronJobMetadata::createByCommand(
            '@weekly',
            $command,
            '--verbose',
            2,
        );

        // Verify that properties are accessible
        self::assertSame('@weekly', $metadata->expression);
        self::assertSame('test:command', $metadata->command);
        self::assertSame('--verbose', $metadata->arguments);
        self::assertSame(2, $metadata->maxInstances);
        self::assertSame('Test', $metadata->description);
    }
}
