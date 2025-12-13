<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Shapecode\Bundle\CronBundle\Entity\CronJobResult;

/** @extends ServiceEntityRepository<CronJobResult> */
class CronJobResultRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronJobResult::class);
    }

    public function deleteOldLogs(DateTimeInterface $time): void
    {
        $qb = $this->createQueryBuilder('d');

        $qb->delete()
            ->where('d.createdAt <= :createdAt')
            ->setParameter('createdAt', $time)
            ->getQuery()
            ->execute();
    }
}
