<?php

namespace App\Repository;

use App\Entity\Experience;
use App\Entity\Review;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }

    /**
     * @return list<Review>
     */
    public function findLatestForExperience(Experience $experience, int $limit = 10): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->where('r.experience = :experience')
            ->setParameter('experience', $experience)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findOneForUserAndExperience(User $user, Experience $experience): ?Review
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->where('r.user = :user')
            ->andWhere('r.experience = :experience')
            ->setParameter('user', $user)
            ->setParameter('experience', $experience)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
