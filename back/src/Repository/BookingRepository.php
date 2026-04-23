<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\Experience;
use App\Entity\Slot;
use App\Entity\User;
use App\Enum\BookingStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    public function hasActiveBookingForUserAndSlot(User $user, Slot $slot): bool
    {
        return (bool) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->where('b.user = :user')
            ->andWhere('b.slot = :slot')
            ->andWhere('b.status != :cancelled')
            ->setParameter('user', $user)
            ->setParameter('slot', $slot)
            ->setParameter('cancelled', BookingStatus::CANCELLED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<Booking>
     */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->addSelect('s')
            ->join('s.experience', 'e')
            ->addSelect('e')
            ->leftJoin('b.payments', 'p')
            ->addSelect('p')
            ->where('b.user = :user')
            ->setParameter('user', $user)
            ->orderBy('b.createdAt', 'DESC')
            ->addOrderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Booking>
     */
    public function findForOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->addSelect('s')
            ->join('s.experience', 'e')
            ->addSelect('e')
            ->join('b.user', 'u')
            ->addSelect('u')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('s.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function hasParticipatedInExperience(User $user, Experience $experience): bool
    {
        return (bool) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->join('b.slot', 's')
            ->join('s.experience', 'e')
            ->where('b.user = :user')
            ->andWhere('e = :experience')
            ->andWhere('b.status = :paid')
            ->andWhere('s.endAt <= :now')
            ->setParameter('user', $user)
            ->setParameter('experience', $experience)
            ->setParameter('paid', BookingStatus::PAID)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findDetailedByIdForUser(int $id, User $user): ?Booking
    {
        return $this->createQueryBuilder('b')
            ->join('b.slot', 's')
            ->addSelect('s')
            ->join('s.experience', 'e')
            ->addSelect('e')
            ->leftJoin('b.payments', 'p')
            ->addSelect('p')
            ->where('b.id = :id')
            ->andWhere('b.user = :user')
            ->setParameter('id', $id)
            ->setParameter('user', $user)
            ->orderBy('p.createdAt', 'DESC')
            ->getQuery()
            ->getOneOrNullResult();
    }
}
