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
            ->andWhere('p.apartment = :apartment')
            ->setParameter('apartment', $apartment)
            ->orderBy('p.startDate', 'ASC') // En cas de périodes qui se chevauchent, prendre la première
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
        
       

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * Calcule le montant du séjour nuit par nuit : chaque nuit prend le prix de la
     * PricePeriod qui la couvre (sinon le prix de base de l'appartement), pour gérer
     * correctement un séjour qui chevauche le début/la fin d'une période tarifaire.
     */
    public function calculateStayPrice(Apartment $apartment, \DateTimeInterface $startDate, \DateTimeInterface $endDate): array
    {
        $nights = max(1, $startDate->diff($endDate)->days);

        $periods = $this->createQueryBuilder('p')
            ->andWhere('p.apartment = :apartment')
            ->andWhere('p.startDate <= :end')
            ->andWhere('p.endDate >= :start')
            ->setParameter('apartment', $apartment)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->orderBy('p.startDate', 'ASC')
            ->getQuery()
            ->getResult();

        $basePrice = (float) $apartment->getPrice();
        $total = 0.0;
        $breakdown = [];
        $groupedBreakdown = [];
        $night = \DateTime::createFromInterface($startDate);

        for ($i = 0; $i < $nights; $i++) {
            $applicablePeriod = null;
            foreach ($periods as $period) {
                if ($period->getStartDate() <= $night && $period->getEndDate() >= $night) {
                    $applicablePeriod = $period;
                    break;
                }
            }
            $nightPrice = $applicablePeriod ? (float) $applicablePeriod->getPrice() : $basePrice;
            $total += $nightPrice;
            $nightStr = $night->format('Y-m-d');
            $breakdown[] = ['date' => $nightStr, 'price' => $nightPrice];

            $lastGroupIndex = array_key_last($groupedBreakdown);
            if ($lastGroupIndex !== null && $groupedBreakdown[$lastGroupIndex]['price'] === $nightPrice) {
                $groupedBreakdown[$lastGroupIndex]['endDate'] = $nightStr;
            } else {
                $groupedBreakdown[] = ['startDate' => $nightStr, 'endDate' => $nightStr, 'price' => $nightPrice];
            }

            $night->modify('+1 day');
        }

        return [
            'total' => $total,
            'nights' => $nights,
            'pricePerNight' => $total / $nights,
            'breakdown' => $breakdown,
            'groupedBreakdown' => $groupedBreakdown,
        ];
    }
}
