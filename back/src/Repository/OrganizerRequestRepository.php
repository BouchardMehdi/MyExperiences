<?php

namespace App\Repository;

use App\Entity\OrganizerRequest;
use App\Entity\User;
use App\Enum\OrganizerRequestStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OrganizerRequest>
 */
class OrganizerRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizerRequest::class);
    }

    public function findLatestForUser(User $user): ?OrganizerRequest
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.reviewedBy', 'reviewer')
            ->addSelect('reviewer')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPendingForUser(User $user): ?OrganizerRequest
    {
        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', OrganizerRequestStatus::PENDING)
            ->orderBy('r.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<OrganizerRequest>
     */
    public function findPendingDetailed(): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->leftJoin('r.reviewedBy', 'reviewer')
            ->addSelect('reviewer')
            ->where('r.status = :status')
            ->setParameter('status', OrganizerRequestStatus::PENDING)
            ->orderBy('r.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findDetailedById(int $id): ?OrganizerRequest
    {
        return $this->createQueryBuilder('r')
            ->join('r.user', 'u')
            ->addSelect('u')
            ->leftJoin('r.reviewedBy', 'reviewer')
            ->addSelect('reviewer')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
