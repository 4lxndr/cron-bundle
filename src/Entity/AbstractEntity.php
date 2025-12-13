<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Entity;

use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

abstract class AbstractEntity
{
    #[ORM\Column(type: Types::INTEGER, options: ['unsigned' => true])]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public ?DateTimeInterface $createdAt {
        get => $this->createdAt;
        set => $this->createdAt = $value;
    }

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    public ?DateTimeInterface $updatedAt {
        get => $this->updatedAt;
        set => $this->updatedAt = $value;
    }

    public function getId(): ?int
    {
        return $this->id;
    }
}
