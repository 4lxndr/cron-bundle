<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Entity;

use Cron\CronExpression;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;

#[ORM\Entity(repositoryClass: CronJobRepository::class)]
class CronJob extends AbstractEntity
{
    #[ORM\Column(type: Types::STRING)]
    public private(set) string $command {
        get => $this->command;
    }

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $arguments {
        get => $this->arguments;
        set => $this->arguments = $value;
    }

    #[ORM\Column(type: Types::STRING, nullable: true)]
    public ?string $description {
        get => $this->description;
        set => $this->description = $value;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 0])]
    public private(set) int $runningInstances {
        get => $this->runningInstances;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 1])]
    public int $maxInstances {
        get => $this->maxInstances;
        set => $this->maxInstances = $value;
    }

    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true, 'default' => 1])]
    public int $number {
        get => $this->number;
        set => $this->number = $value;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    public ?DateTimeImmutable $lastUse {
        get => $this->lastUse;
        set => $this->lastUse = $value !== null ? DateTimeImmutable::createFromInterface($value) : null;
    }

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    public DateTimeImmutable $nextRun {
        get => $this->nextRun;
        set => $this->nextRun = DateTimeImmutable::createFromInterface($value);
    }

    /** @var Collection<int, CronJobResult> */
    #[ORM\OneToMany(targetEntity: CronJobResult::class, mappedBy: 'cronJob', cascade: ['persist', 'remove'], orphanRemoval: true)]
    public private(set) Collection $results {
        get => $this->results;
    }

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    public bool $enable {
        get => $this->enable;
        set => $this->enable = $value;
    }

    #[ORM\Column(type: Types::STRING)]
    public string $period {
        get => $this->period;
        set => $this->period = $value;
    }

    public function __construct(string $command, string $period)
    {
        $this->command = $command;
        $this->arguments = null;
        $this->description = null;
        $this->runningInstances = 0;
        $this->maxInstances = 1;
        $this->number = 1;
        $this->lastUse = null;
        $this->results = new ArrayCollection();
        $this->enable = true;
        $this->period = $period;
        $this->createdAt = null;
        $this->updatedAt = null;

        $this->calculateNextRun();
    }

    public function getFullCommand(): string
    {
        $arguments = '';

        if ($this->arguments !== null) {
            $arguments = ' '.$this->arguments;
        }

        return $this->command.$arguments;
    }

    public function increaseRunningInstances(): void
    {
        ++$this->runningInstances;
    }

    public function decreaseRunningInstances(): void
    {
        --$this->runningInstances;
    }

    public function enable(): void
    {
        $this->enable = true;
    }

    public function disable(): void
    {
        $this->enable = false;
    }

    public function calculateNextRun(): void
    {
        $cron = new CronExpression($this->period);
        $this->nextRun = DateTimeImmutable::createFromMutable($cron->getNextRunDate());
    }

    public function __toString(): string
    {
        return $this->command;
    }
}
