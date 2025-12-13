<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\Collection;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Shapecode\Bundle\CronBundle\Collection\CronJobMetadataCollection;
use Shapecode\Bundle\CronBundle\Domain\CronJobMetadata;
use Symfony\Component\Console\Command\Command;

#[CoversClass(CronJobMetadataCollection::class)]
class CronJobMetadataCollectionTest extends TestCase
{
    public function testConstructorWithVariadicArguments(): void
    {
        $command1 = self::createStub(Command::class);
        $command1->method('getName')->willReturn('command1');
        $command1->method('getDescription')->willReturn('Desc 1');

        $command2 = self::createStub(Command::class);
        $command2->method('getName')->willReturn('command2');
        $command2->method('getDescription')->willReturn('Desc 2');

        $metadata1 = CronJobMetadata::createByCommand('@daily', $command1);
        $metadata2 = CronJobMetadata::createByCommand('@hourly', $command2);

        $collection = new CronJobMetadataCollection($metadata1, $metadata2);

        self::assertCount(2, $collection);
        self::assertTrue($collection->contains($metadata1));
        self::assertTrue($collection->contains($metadata2));
    }

    public function testConstructorWithNoArguments(): void
    {
        $collection = new CronJobMetadataCollection();

        self::assertCount(0, $collection);
        self::assertTrue($collection->isEmpty());
    }

    public function testFirstMethod(): void
    {
        $command1 = self::createStub(Command::class);
        $command1->method('getName')->willReturn('command1');
        $command1->method('getDescription')->willReturn('Desc 1');

        $command2 = self::createStub(Command::class);
        $command2->method('getName')->willReturn('command2');
        $command2->method('getDescription')->willReturn('Desc 2');

        $metadata1 = CronJobMetadata::createByCommand('@daily', $command1);
        $metadata2 = CronJobMetadata::createByCommand('@hourly', $command2);

        $collection = new CronJobMetadataCollection($metadata1, $metadata2);

        $first = $collection->first();

        self::assertSame($metadata1, $first);
    }

    public function testFirstMethodWithEmptyCollection(): void
    {
        $collection = new CronJobMetadataCollection();

        $first = $collection->first();

        self::assertFalse($first);
    }

    public function testInheritsArrayCollectionMethods(): void
    {
        $command = self::createStub(Command::class);
        $command->method('getName')->willReturn('command1');
        $command->method('getDescription')->willReturn('Desc');

        $metadata1 = CronJobMetadata::createByCommand('@daily', $command);

        $collection = new CronJobMetadataCollection($metadata1);

        // Test add method from ArrayCollection
        $metadata2 = CronJobMetadata::createByCommand('@hourly', $command);
        $collection->add($metadata2);

        self::assertCount(2, $collection);
        self::assertTrue($collection->contains($metadata2));
    }
}
