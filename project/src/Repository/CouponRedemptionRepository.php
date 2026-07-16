<?php

namespace App\Repository;

use App\Entity\Coupon;
use App\Entity\CouponRedemption;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CouponRedemption>
 *
 * @method CouponRedemption|null find($id, $lockMode = null, $lockVersion = null)
 * @method CouponRedemption|null findOneBy(array $criteria, array $orderBy = null)
 * @method CouponRedemption[]    findAll()
 * @method CouponRedemption[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CouponRedemptionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CouponRedemption::class);
    }

    public function countByCoupon(Coupon $coupon): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.coupon = :coupon')
            ->setParameter('coupon', $coupon)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCouponAndUser(Coupon $coupon, User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.coupon = :coupon')
            ->andWhere('r.user = :user')
            ->setParameter('coupon', $coupon)
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
