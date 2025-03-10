<?php

namespace App\Repository;

use App\Entity\Apartment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Apartment>
 *
 * @method Apartment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Apartment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Apartment[]    findAll()
 * @method Apartment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ApartmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Apartment::class);
    }

    //    /**
    //     * @return Apartment[] Returns an array of Apartment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Apartment
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function countApartmentFiltered($search): int
    {
        $entityName = Apartment::class;

        $dql = "
        SELECT
            COUNT('*')
        FROM
            $entityName a
        WHERE
            a.name IS NOT NULL AND
            a.name != ''
        ";

        if ($search != '') {
            $dql .= "
            AND (
                a.name LIKE :search
            )
        ";
        }

        $em = $this->getEntityManager()->createQuery($dql);

        if ($search != '') {
            $em->setParameter('search', "%$search%");
        }

        return (int) $em->getSingleScalarResult();
    }

      /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function findAllFiltered($page, $nombreMaxPage, $orderBy, $search = ''): array
    {
        $entityName = Apartment::class;

        if ($orderBy) {
            $exploded_order         = explode(' ', $orderBy);
            $exploded_order[0]      = $exploded_order[0] == 'apartment.name' ? 'apartment.id' : $exploded_order[0];
            $orderBy                = implode(' ', $exploded_order);
        }

        $orderBy = $orderBy ?: "apartment.id DESC";

        $dql = "
            SELECT DISTINCT
                apartment.id,
                apartment.name,
                apartment.description,
                apartment.is_active,
                apartment.is_favorite
               
            FROM
                $entityName apartment
            
            WHERE
                apartment.name IS NOT NULL AND
                apartment.name != '' 
                
                
        ";

        if ($search != '') {
            $dql .= "
                AND (
                    apartment.name LIKE :search 
                )
            ";
        }


        $dql .= " ORDER BY $orderBy ";

        $em = $this->getEntityManager()
        ->createQuery($dql);

        if ($search != '') {
            $em->setParameter('search', "%$search%");
        }



        $em->setMaxResults($nombreMaxPage)
           ->setFirstResult($page);
        ;
        
        return [$em->getResult(), $this->countApartmentFiltered($search)];
    }
}
