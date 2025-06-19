<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Belirli bir haber için onaylanmış yorumları bulur
     */
    public function findApprovedCommentsByNews($newsId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.news = :newsId')
            ->andWhere('c.isApproved = :approved')
            ->setParameter('newsId', $newsId)
            ->setParameter('approved', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Onay bekleyen yorumları bulur
     */
    public function findPendingComments(): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isApproved = :isApproved')
            ->setParameter('isApproved', false)
            ->orderBy('c.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Belirli bir kullanıcının yorumlarını bulur
     */
    public function findCommentsByUser($userId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Son eklenen yorumları bulur
     */
    public function findRecentComments(int $limit = 10): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.isApproved = :isApproved')
            ->setParameter('isApproved', true)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
