<?php

namespace App\Repository;

use App\Entity\MinimumStayPeriod;
use App\Entity\Apartment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MinimumStayPeriod>
 */
class MinimumStayPeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MinimumStayPeriod::class);
    }

    /**
     * Find minimum stay period for a given date
     */
    public function findForDate(Apartment $apartment, \DateTimeInterface $date): ?MinimumStayPeriod
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.apartment = :apartment')
            ->andWhere('m.startDate <= :date')
            ->andWhere('m.endDate >= :date')
            ->orderBy('m.startDate', 'DESC')
            ->setParameter('apartment', $apartment)
            ->setParameter('date', $date)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Find all minimum stay periods for an apartment
     */
    public function findByApartment(Apartment $apartment): array
    {
        return $this->createQueryBuilder('m')
            ->andWhere('m.apartment = :apartment')
            ->orderBy('m.startDate', 'ASC')
            ->setParameter('apartment', $apartment)
            ->getQuery()
            ->getResult();
    }
}
