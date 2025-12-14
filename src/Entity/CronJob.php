<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Entity;

use Cron\CronExpression;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shapecode\Bundle\CronBundle\Domain\DependencyFailureMode;
use Shapecode\Bundle\CronBundle\Domain\DependencyMode;
use Shapecode\Bundle\CronBundle\Repository\CronJobRepository;

use function in_array;

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

    /** @var list<string> */
    #[ORM\Column(type: Types::JSON)]
    public array $tags {
        get => $this->tags;
        set => $this->tags = $value;
    }

    #[ORM\Column(type: Types::STRING, enumType: DependencyMode::class)]
    public DependencyMode $dependencyMode {
        get => $this->dependencyMode;
        set => $this->dependencyMode = $value;
    }

    #[ORM\Column(type: Types::STRING, enumType: DependencyFailureMode::class)]
    public DependencyFailureMode $onDependencyFailure {
        get => $this->onDependencyFailure;
        set => $this->onDependencyFailure = $value;
    }

    /** @var Collection<int, CronJob> */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'cron_job_dependencies')]
    #[ORM\JoinColumn(name: 'cron_job_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'depends_on_id', referencedColumnName: 'id')]
    public private(set) Collection $dependencies {
        get => $this->dependencies;
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
        $this->tags = [];
        $this->dependencies = new ArrayCollection();
        $this->dependencyMode = DependencyMode::AND;
        $this->onDependencyFailure = DependencyFailureMode::SKIP;
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

    public function addDependency(self $dependency): void
    {
        if (!$this->dependencies->contains($dependency)) {
            $this->dependencies->add($dependency);
        }
    }

    public function removeDependency(self $dependency): void
    {
        $this->dependencies->removeElement($dependency);
    }

    public function clearDependencies(): void
    {
        $this->dependencies->clear();
    }

    /** @param list<string> $tags */
    public function hasTags(array $tags): bool
    {
        if ($tags === []) {
            return true; // Empty search matches all
        }

        foreach ($tags as $tag) {
            if (!in_array($tag, $this->tags, true)) {
                return false; // If any tag doesn't match, return false
            }
        }

        return true; // All tags matched
    }

    public function __toString(): string
    {
        return $this->command;
    }
}
