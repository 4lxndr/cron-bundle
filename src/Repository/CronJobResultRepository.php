<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Repository;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Shapecode\Bundle\CronBundle\Entity\CronJob;
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

    /**
     * Find the most recent result for a job.
     */
    public function findLatestByJob(CronJob $job): ?CronJobResult
    {
        /** @var CronJobResult|null */
        return $this->createQueryBuilder('r')
            ->where('r.cronJob = :job')
            ->setParameter('job', $job)
            ->orderBy('r.runAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
