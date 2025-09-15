<?php

namespace App\Repository;

use App\Entity\Apartment;
use App\Entity\PricePeriod;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PricePeriod>
 *
 * @method PricePeriod|null find($id, $lockMode = null, $lockVersion = null)
 * @method PricePeriod|null findOneBy(array $criteria, array $orderBy = null)
 * @method PricePeriod[]    findAll()
 * @method PricePeriod[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PricePeriodRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PricePeriod::class);
    }

    //    /**
    //     * @return PricePeriod[] Returns an array of PricePeriod objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PricePeriod
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    /**
     * Trouve la période de prix active pour une date donnée (par défaut aujourd'hui)
     */
    public function findCurrentPricePeriod(\DateTimeInterface $date = null, Apartment $apartment): ?PricePeriod
    {
        if ($date === null) {
            $date = new \DateTime('today'); // Aujourd'hui à 00:00:00
        }

        return $this->createQueryBuilder('p')
            ->andWhere('p.startDate <= :date')
            ->andWhere('p.endDate >= :date')
            ->setParameter('date', $date)
            ->orderBy('p.startDate', 'ASC') // En cas de périodes qui se chevauchent, prendre la première
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
        if ($apartment !== null) {
        $qb->andWhere('p.apartment = :apartment')
           ->setParameter('apartment', $apartment);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }


   
}
