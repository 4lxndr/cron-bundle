<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\CronJob;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\CronJob\CommandRegistry;
use Symfony\Component\Console\Command\Command;

#[CoversClass(CommandRegistry::class)]
final class CommandRegistryTest extends TestCase
{
    public function testRegisterCommand(): void
    {
        $registry = new CommandRegistry();

        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');

        $registry->register($command);

        $resolved = $registry->resolveClassName($command::class);

        self::assertSame('test:command', $resolved);
    }

    public function testResolveUnknownClass(): void
    {
        $registry = new CommandRegistry();

        // @phpstan-ignore argument.type
        $resolved = $registry->resolveClassName('NonExistent\\Command');

        self::assertNull($resolved);
    }

    public function testResolveMultipleClassNames(): void
    {
        $registry = new CommandRegistry();

        // Create two different anonymous command classes to have distinct class names
        $command1 = new class ('test:command1') extends Command {
            public function __construct(
                /** @phpstan-ignore property.phpDocType */
                private readonly string $name,
            ) {
                parent::__construct();
            }

            /** @phpstan-ignore return.unusedType */
            public function getName(): ?string
            {
                return $this->name;
            }
        };

        $command2 = new class ('test:command2') extends Command {
            public function __construct(
                /** @phpstan-ignore property.phpDocType */
                private readonly string $name,
            ) {
                parent::__construct();
            }

            /** @phpstan-ignore return.unusedType */
            public function getName(): ?string
            {
                return $this->name;
            }
        };

        $registry->register($command1);
        $registry->register($command2);

        /** @var list<class-string> $classNames */
        $classNames = [
            $command1::class,
            $command2::class,
            'NonExistent\\Command',
        ];
        $resolved = $registry->resolveClassNames($classNames);

        self::assertSame(['test:command1', 'test:command2'], $resolved);
    }

    public function testClear(): void
    {
        $registry = new CommandRegistry();

        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('test:command');

        $registry->register($command);

        self::assertSame('test:command', $registry->resolveClassName($command::class));

        $registry->clear();

        self::assertNull($registry->resolveClassName($command::class));
    }

    public function testIgnoreCommandWithoutName(): void
    {
        $registry = new CommandRegistry();

        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn(null);

        $registry->register($command);

        $resolved = $registry->resolveClassName($command::class);

        self::assertNull($resolved);
    }
}
