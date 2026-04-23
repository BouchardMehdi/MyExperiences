<?php

namespace App\Repository;

use App\Entity\Experience;
use App\Entity\Slot;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Slot>
 */
class SlotRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Slot::class);
    }

    /**
     * @return list<Slot>
     */
    public function findBookableForExperience(Experience $experience): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.experience = :experience')
            ->andWhere('s.isActive = true')
            ->andWhere('s.startAt > :now')
            ->setParameter('experience', $experience)
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Slot>
     */
    public function findForOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.experience', 'e')
            ->addSelect('e')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('s.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
