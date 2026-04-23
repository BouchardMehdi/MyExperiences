<?php

namespace App\Repository;

use App\Entity\Experience;
use App\Entity\User;
use App\Enum\ExperienceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Experience>
 */
class ExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Experience::class);
    }

    /**
     * @return list<Experience>
     */
    public function findPublishedWithFilters(?string $location, ?float $maxPrice, ?\DateTimeImmutable $date): array
    {
        $qb = $this->createQueryBuilder('e')
            ->distinct()
            ->leftJoin('e.slots', 's')
            ->addSelect('s')
            ->where('e.status = :status')
            ->setParameter('status', ExperienceStatus::PUBLISHED)
            ->orderBy('e.createdAt', 'DESC');

        if ($location) {
            $qb->andWhere('LOWER(e.location) LIKE :location')
                ->setParameter('location', '%'.mb_strtolower($location).'%');
        }

        if (null !== $maxPrice) {
            $qb->andWhere('e.price <= :maxPrice')
                ->setParameter('maxPrice', number_format($maxPrice, 2, '.', ''));
        }

        if ($date) {
            $startOfDay = $date->setTime(0, 0);
            $endOfDay = $date->setTime(23, 59, 59);

            $qb->andWhere('s.startAt BETWEEN :startOfDay AND :endOfDay')
                ->andWhere('s.isActive = true')
                ->setParameter('startOfDay', $startOfDay)
                ->setParameter('endOfDay', $endOfDay);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return list<Experience>
     */
    public function findForOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return list<Experience>
     */
    public function findDetailedForOrganizer(User $organizer): array
    {
        return $this->createQueryBuilder('e')
            ->distinct()
            ->leftJoin('e.slots', 's')
            ->addSelect('s')
            ->leftJoin('e.reviews', 'r')
            ->addSelect('r')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $organizer)
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findOneForOrganizer(int $id, User $organizer): ?Experience
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.slots', 's')
            ->addSelect('s')
            ->leftJoin('e.reviews', 'r')
            ->addSelect('r')
            ->where('e.id = :id')
            ->andWhere('e.organizer = :organizer')
            ->setParameter('id', $id)
            ->setParameter('organizer', $organizer)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublishedById(int $id): ?Experience
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.slots', 's')
            ->addSelect('s')
            ->leftJoin('e.reviews', 'r')
            ->addSelect('r')
            ->where('e.id = :id')
            ->andWhere('e.status = :status')
            ->setParameter('id', $id)
            ->setParameter('status', ExperienceStatus::PUBLISHED)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return list<Experience>
     */
    public function findAllDetailed(): array
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.organizer', 'o')
            ->addSelect('o')
            ->leftJoin('e.slots', 's')
            ->addSelect('s')
            ->leftJoin('e.reviews', 'r')
            ->addSelect('r')
            ->orderBy('e.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findDetailedById(int $id): ?Experience
    {
        return $this->createQueryBuilder('e')
            ->leftJoin('e.organizer', 'o')
            ->addSelect('o')
            ->leftJoin('e.slots', 's')
            ->addSelect('s')
            ->leftJoin('e.reviews', 'r')
            ->addSelect('r')
            ->where('e.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
