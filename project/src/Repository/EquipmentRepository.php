<?php

namespace App\Repository;

use App\Entity\Equipment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Equipment>
 *
 * @method Equipment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Equipment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Equipment[]    findAll()
 * @method Equipment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EquipmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Equipment::class);
    }

    //    /**
    //     * @return Equipment[] Returns an array of Equipment objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('e.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Equipment
    //    {
    //        return $this->createQueryBuilder('e')
    //            ->andWhere('e.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function countEquipmentFiltered($search): int
    {
        $entityName = Equipment::class;

        $dql = "
        SELECT
            COUNT('*')
        FROM
            $entityName e
        WHERE
            e.name IS NOT NULL AND
            e.name != ''
        ";

        if ($search != '') {
            $dql .= "
            AND (
                e.name LIKE :search
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
        $entityName = Equipment::class;

        if ($orderBy) {
            $exploded_order         = explode(' ', $orderBy);
            $exploded_order[0]      = $exploded_order[0] == 'e.name' ? 'e.id' : $exploded_order[0];
            $orderBy                = implode(' ', $exploded_order);
        }

        $orderBy = $orderBy ?: "e.id DESC";

        $dql = "
            SELECT DISTINCT
                e.id,
                e.name
               
            FROM
                $entityName e
            
            WHERE
                e.name IS NOT NULL AND
                e.name != '' 
                
                
        ";

        if ($search != '') {
            $dql .= "
                AND (
                    e.name LIKE :search 
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
        
        return [$em->getResult(), $this->countEquipmentFiltered($search)];
    }
}
