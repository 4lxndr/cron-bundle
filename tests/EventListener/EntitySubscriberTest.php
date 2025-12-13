<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Tests\EventListener;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Psr\Clock\ClockInterface;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
use Shapecode\Bundle\CronBundle\EventListener\EntitySubscriber;
use stdClass;

class EntitySubscriberTest extends TestCase
{
    private ClockInterface & Stub $clock;

    private EntitySubscriber $subscriber;

    protected function setUp(): void
    {
        $this->clock      = self::createStub(ClockInterface::class);
        $this->subscriber = new EntitySubscriber($this->clock);
    }

    public function testPrePersistSetsCreatedAtAndUpdatedAtWhenNull(): void
    {
        $now = new DateTimeImmutable('2024-10-10 12:00:00');
        $this->clock->method('now')->willReturn($now);

        $entity = new CronJob('test-command', '@daily');

        $entityManager = self::createStub(EntityManagerInterface::class);
        $args          = new LifecycleEventArgs($entity, $entityManager);

        $this->subscriber->prePersist($args);

        self::assertInstanceOf(DateTimeImmutable::class, $entity->createdAt);
        self::assertInstanceOf(DateTimeImmutable::class, $entity->updatedAt);
        self::assertEquals('2024-10-10 12:00:00', $entity->createdAt->format('Y-m-d H:i:s'));
        self::assertEquals('2024-10-10 12:00:00', $entity->updatedAt->format('Y-m-d H:i:s'));
    }

    public function testPreUpdateSetsUpdatedAt(): void
    {
        $entity = new CronJob('test-command', '@daily');
        $entityManager = self::createStub(EntityManagerInterface::class);
        $args = new LifecycleEventArgs($entity, $entityManager);

        // First, call prePersist to set createdAt and updatedAt
        $initialTime = new DateTimeImmutable('2024-01-01 10:00:00');
        $this->clock->method('now')->willReturn($initialTime);
        $this->subscriber->prePersist($args);

        $originalCreatedAt = $entity->createdAt;
        $originalUpdatedAt = $entity->updatedAt;

        // Then call preUpdate with a different time
        $updateTime = new DateTimeImmutable('2024-10-10 12:00:00');
        $this->clock = self::createStub(ClockInterface::class);
        $this->clock->method('now')->willReturn($updateTime);
        $this->subscriber = new EntitySubscriber($this->clock);
        $this->subscriber->preUpdate($args);

        self::assertSame($originalCreatedAt, $entity->createdAt, 'createdAt should not change on update');
        self::assertNotSame($originalUpdatedAt, $entity->updatedAt, 'updatedAt should change on update');
        self::assertNotNull($entity->updatedAt);
        self::assertEquals('2024-10-10 12:00:00', $entity->updatedAt->format('Y-m-d H:i:s'));
    }

    public function testEntityNotInstanceOfAbstractEntity(): void
    {
        $nonEntity = new stdClass();

        $entityManager = self::createStub(EntityManagerInterface::class);
        $args          = new LifecycleEventArgs($nonEntity, $entityManager);

        $this->subscriber->prePersist($args);
        $this->subscriber->preUpdate($args);

        $this->expectNotToPerformAssertions();
    }
}
