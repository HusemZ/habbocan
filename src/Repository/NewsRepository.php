<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<News>
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @return News[] Returns an array of News objects
     */
    public function findLatestNews(int $limit = 10): array
    {
        return $this->createQueryBuilder('n')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return News[] Returns an array of pinned News objects
     */
    public function findPinnedNews(): array
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.isPinned = :val')
            ->setParameter('val', true)
            ->orderBy('n.createdAt', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneByTitle(string $title): ?News
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.title = :val')
            ->setParameter('val', $title)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
