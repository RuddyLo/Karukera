<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    // Stripe PaymentIntent ID (pi_xxx)
    #[ORM\Column(length: 255, unique: true)]
    private ?string $stripe_payment_intent_id = null;

    // Amount authorized (deposit) in cents
    #[ORM\Column]
    private ?int $amount_authorized = null;

    // Amount captured later (nullable)
    #[ORM\Column(nullable: true)]
    private ?int $amount_captured = null;

    #[ORM\Column(length: 10)]
    private ?string $currency = null;

    // authorized | captured | canceled | refunded
    #[ORM\Column(length: 20)]
    private ?string $status = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne(inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Reservation $reservation = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStripePaymentIntentId(): ?string
    {
        return $this->stripe_payment_intent_id;
    }

    public function setStripePaymentIntentId(string $id): static
    {
        $this->stripe_payment_intent_id = $id;
        return $this;
    }

    public function getAmountAuthorized(): ?int
    {
        return $this->amount_authorized;
    }

    public function setAmountAuthorized(int $amount): static
    {
        $this->amount_authorized = $amount;
        return $this;
    }

    public function getAmountCaptured(): ?int
    {
        return $this->amount_captured;
    }

    public function setAmountCaptured(?int $amount): static
    {
        $this->amount_captured = $amount;
        return $this;
    }

    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function getReservation(): ?Reservation
    {
        return $this->reservation;
    }

    public function setReservation(Reservation $reservation): static
    {
        $this->reservation = $reservation;
        return $this;
    }
}
