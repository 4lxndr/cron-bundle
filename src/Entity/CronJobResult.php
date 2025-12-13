<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Shapecode\Bundle\CronBundle\Repository\CronJobResultRepository;

use function sprintf;

#[ORM\Entity(repositoryClass: CronJobResultRepository::class)]
class CronJobResult extends AbstractEntity
{
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public private(set) DateTimeInterface $runAt {
        get => $this->runAt;
    }

    #[ORM\Column(type: Types::FLOAT)]
    public private(set) float $runTime {
        get => $this->runTime;
    }

    #[ORM\Column(type: Types::INTEGER)]
    public private(set) int $statusCode {
        get => $this->statusCode;
    }

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public private(set) ?string $output {
        get => $this->output;
    }

    #[ORM\ManyToOne(targetEntity: CronJob::class, cascade: ['persist'], inversedBy: 'results')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    public private(set) CronJob $cronJob {
        get => $this->cronJob;
    }

    public function __construct(
        CronJob $cronJob,
        float $runTime,
        int $statusCode,
        ?string $output,
        DateTimeInterface $runAt,
    ) {
        $this->runTime = $runTime;
        $this->statusCode = $statusCode;
        $this->output = $output;
        $this->cronJob = $cronJob;
        $this->runAt = DateTime::createFromInterface($runAt);
        $this->createdAt = null;
        $this->updatedAt = null;
    }

    public function __toString(): string
    {
        return sprintf(
            '%s - %s',
            $this->cronJob->command,
            $this->runAt->format('d.m.Y H:i P'),
        );
    }
}
