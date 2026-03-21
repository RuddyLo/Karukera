<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reservations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Apartment $apartment = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $startDate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $endDate = null;

    #[ORM\Column(nullable: true)]
    private ?bool $confirmed = null;

    // 🔹 NEW — payments linked to this reservation
    #[ORM\OneToMany(mappedBy: 'reservation', targetEntity: Payment::class, cascade: ['remove'])]
    private Collection $payments;

    // 🔹 OPTIONAL — reservation lifecycle
    #[ORM\Column(length: 20)]
    private string $status = 'pending';
    // pending | confirmed | canceled | completed

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $rentPaymentIntentId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cautionPaymentIntentId = null;

    #[ORM\Column]
    private ?bool $caution_refunded = null;

    #[ORM\Column]
    private ?bool $caution_concerved = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $reference = null;

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function generateReference(): string
    {
        $userPart  = strtoupper(substr($this->user->getEmail(), 0, 3));
        $apartPart = strtoupper(substr($this->apartment->getName(), 0, 3));
        $datePart  = $this->createdAt->format('Ymd');
        $randPart  = strtoupper(substr(uniqid(), -4));

        return $userPart . '-' . $apartPart . '-' . $datePart . '-' . $randPart;
    }

    public function __construct()
    {
        $this->payments  = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->confirmed = false;
    }

    // --------------------
    // Getters & setters
    // --------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getApartment(): ?Apartment
    {
        return $this->apartment;
    }

    public function setApartment(?Apartment $apartment): static
    {
        $this->apartment = $apartment;
        return $this;
    }

    public function getStartDate(): ?\DateTimeInterface
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeInterface $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeInterface
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeInterface $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    public function isConfirmed(): ?bool
    {
        return $this->confirmed;
    }

    public function setConfirmed(?bool $confirmed): static
    {
        $this->confirmed = $confirmed;
        return $this;
    }

    // --------------------
    // Payments
    // --------------------

    /**
     * @return Collection<int, Payment>
     */
    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setReservation($this);
        }
        return $this;
    }

    // --------------------
    // Status / lifecycle
    // --------------------

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getRentPaymentIntentId(): ?string
    {
        return $this->rentPaymentIntentId;
    }

    public function setRentPaymentIntentId(?string $rentPaymentIntentId): static
    {
        $this->rentPaymentIntentId = $rentPaymentIntentId;

        return $this;
    }

    public function getCautionPaymentIntentId(): ?string
    {
        return $this->cautionPaymentIntentId;
    }

    public function setCautionPaymentIntentId(?string $cautionPaymentIntentId): static
    {
        $this->cautionPaymentIntentId = $cautionPaymentIntentId;

        return $this;
    }

    public function isCautionRefunded(): ?bool
    {
        return $this->caution_refunded;
    }

    public function setCautionRefunded(bool $caution_refunded): static
    {
        $this->caution_refunded = $caution_refunded;

        return $this;
    }

    public function isCautionConcerved(): ?bool
    {
        return $this->caution_concerved;
    }

    public function setCautionConcerved(bool $caution_concerved): static
    {
        $this->caution_concerved = $caution_concerved;

        return $this;
    }
}
