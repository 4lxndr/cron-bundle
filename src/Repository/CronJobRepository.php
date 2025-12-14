<?php

declare(strict_types=1);

namespace Shapecode\Bundle\CronBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Persistence\ManagerRegistry;
use Shapecode\Bundle\CronBundle\Collection\CronJobCollection;
use Shapecode\Bundle\CronBundle\Entity\CronJob;

/** @extends ServiceEntityRepository<CronJob> */
class CronJobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CronJob::class);
    }

    public function findOneByCommand(string $command, int $number = 1): ?CronJob
    {
        return $this->findOneBy([
            'command' => $command,
            'number' => $number,
        ]);
    }

    public function findAllCollection(): CronJobCollection
    {
        return new CronJobCollection(...$this->findAll());
    }

    public function findByCommandOrId(string $commandOrId): CronJobCollection
    {
        $qb = $this->createQueryBuilder('p');

        /** @var list<CronJob> $result */
        $result = $qb
            ->andWhere(
                $qb->expr()->orX(
                    'p.command = :command',
                    'p.id = :commandInt',
                ),
            )
            ->setParameter('command', $commandOrId, Types::STRING)
            ->setParameter('commandInt', (int) $commandOrId, Types::INTEGER)
            ->getQuery()
            ->getResult();

        return new CronJobCollection(...$result);
    }

    /**
     * Find jobs by tags (matches if job has ANY of the provided tags).
     *
     * @param list<string> $tags
     */
    public function findByTags(array $tags): CronJobCollection
    {
        if ($tags === []) {
            return new CronJobCollection();
        }

        $qb = $this->createQueryBuilder('j');

        $conditions = [];
        foreach ($tags as $index => $tag) {
            $conditions[] = $qb->expr()->like(
                'j.tags',
                $qb->expr()->literal('%"'.$tag.'"%'),
            );
        }

        /** @var list<CronJob> $result */
        $result = $qb
            ->where($qb->expr()->orX(...$conditions))
            ->getQuery()
            ->getResult();

        return new CronJobCollection(...$result);
    }

    /**
     * Find all jobs that have dependencies.
     */
    public function findJobsWithDependencies(): CronJobCollection
    {
        $qb = $this->createQueryBuilder('j');

        /** @var list<CronJob> $result */
        $result = $qb
            ->leftJoin('j.dependencies', 'd')
            ->where('d.id IS NOT NULL')
            ->getQuery()
            ->getResult();

        return new CronJobCollection(...$result);
    }

    /**
     * Find jobs by command name or ID, optionally filtered by tags.
     *
     * @param list<string> $tags
     */
    public function findByCommandOrIdWithTags(string $commandOrId, array $tags = []): CronJobCollection
    {
        $qb = $this->createQueryBuilder('p');

        $qb->andWhere(
            $qb->expr()->orX(
                'p.command = :command',
                'p.id = :commandInt',
            ),
        )
        ->setParameter('command', $commandOrId, Types::STRING)
        ->setParameter('commandInt', (int) $commandOrId, Types::INTEGER);

        if ($tags !== []) {
            $tagConditions = [];
            foreach ($tags as $tag) {
                $tagConditions[] = $qb->expr()->like(
                    'p.tags',
                    $qb->expr()->literal('%"'.$tag.'"%'),
                );
            }

            $qb->andWhere($qb->expr()->orX(...$tagConditions));
        }

        /** @var list<CronJob> $result */
        $result = $qb->getQuery()->getResult();

        return new CronJobCollection(...$result);
    }
}
