<?php

namespace App\Repository;

use App\Entity\Payment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class PaymentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Payment::class);
    }

    public function findOneByPaymentIntent(string $paymentIntentId): ?Payment
    {
        return $this->findOneBy([
            'stripe_payment_intent_id' => $paymentIntentId
        ]);
    }
}
