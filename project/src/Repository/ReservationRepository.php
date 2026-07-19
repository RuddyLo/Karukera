<?php

namespace App\Repository;

use App\Entity\Reservation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 *
 * @method Reservation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Reservation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Reservation[]    findAll()
 * @method Reservation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    //    /**
    //     * @return Reservation[] Returns an array of Reservation objects
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

    //    public function findOneBySomeField($value): ?Reservation
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }


    public function findOverlappingApartmentIds(string $start, string $end): array
    {
        $rows = $this->createQueryBuilder('r')
            ->select('IDENTITY(r.apartment) as apartment_id')
            ->where('r.startDate < :end')
            ->andWhere('r.endDate > :start')
            ->andWhere('r.status NOT IN (:canceled)')
            ->setParameter('start', new \DateTime($start))
            ->setParameter('end', new \DateTime($end))
            ->setParameter('canceled', ['canceled'])
            ->getQuery()
            ->getScalarResult();

        return array_column($rows, 'apartment_id');
    }

    public function findReservationsBetweenDates($apartment, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.apartment = :apartment')
            ->andWhere('
                (r.startDate < :end AND r.endDate > :start)
            ')
            ->setParameter('apartment', $apartment)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array{0: Reservation[], 1: int}
     */
    public function findAllFiltered(int $page, int $length, ?string $orderBy, string $search, string $statusFilter): array
    {
        $qb = $this->createQueryBuilder('r')
            ->leftJoin('r.user', 'u')->addSelect('u')
            ->leftJoin('r.apartment', 'a')->addSelect('a');

        if ($search !== '') {
            $qb->andWhere('u.email LIKE :search OR a.name LIKE :search OR r.reference LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $now = new \DateTime('today');
        if ($statusFilter === 'canceled') {
            $qb->andWhere('r.status = :status')->setParameter('status', 'canceled');
        } elseif (\in_array($statusFilter, ['upcoming', 'ongoing', 'finished'], true)) {
            $qb->andWhere('r.status != :status')->setParameter('status', 'canceled');
            if ($statusFilter === 'upcoming') {
                $qb->andWhere('r.startDate > :now')->setParameter('now', $now);
            } elseif ($statusFilter === 'ongoing') {
                $qb->andWhere('r.startDate <= :now')->andWhere('r.endDate >= :now')->setParameter('now', $now);
            } else {
                $qb->andWhere('r.endDate < :now')->setParameter('now', $now);
            }
        }

        $total = (int) (clone $qb)->select('COUNT(DISTINCT r.id)')->getQuery()->getSingleScalarResult();

        if ($orderBy) {
            [$field, $dir] = array_pad(explode(' ', $orderBy), 2, 'DESC');
        } else {
            $field = 'r.startDate';
            $dir = 'DESC';
        }

        $qb->orderBy($field, $dir)
            ->setFirstResult($page)
            ->setMaxResults($length);

        return [$qb->getQuery()->getResult(), $total];
    }
}
