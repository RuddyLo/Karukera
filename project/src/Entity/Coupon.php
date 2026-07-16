<?php

namespace App\Entity;

use App\Repository\CouponRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CouponRepository::class)]
class Coupon
{
    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $code = null;

    #[ORM\Column(length: 20)]
    private string $type = self::TYPE_PERCENTAGE;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $value = null;

    #[ORM\Column(options: ['default' => true])]
    private bool $isActive = true;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validFrom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $validUntil = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxUses = null;

    #[ORM\Column(nullable: true, options: ['default' => 1])]
    private ?int $maxUsesPerUser = 1;

    #[ORM\ManyToMany(targetEntity: Apartment::class)]
    private Collection $apartments;

    #[ORM\OneToMany(targetEntity: CouponRedemption::class, mappedBy: 'coupon')]
    private Collection $redemptions;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->apartments = new ArrayCollection();
        $this->redemptions = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = strtoupper(trim($code));

        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getValue(): ?string
    {
        return $this->value;
    }

    public function setValue(string $value): static
    {
        $this->value = $value;

        return $this;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function setIsActive(bool $isActive): static
    {
        $this->isActive = $isActive;

        return $this;
    }

    public function getValidFrom(): ?\DateTimeInterface
    {
        return $this->validFrom;
    }

    public function setValidFrom(?\DateTimeInterface $validFrom): static
    {
        $this->validFrom = $validFrom;

        return $this;
    }

    public function getValidUntil(): ?\DateTimeInterface
    {
        return $this->validUntil;
    }

    public function setValidUntil(?\DateTimeInterface $validUntil): static
    {
        $this->validUntil = $validUntil;

        return $this;
    }

    public function getMaxUses(): ?int
    {
        return $this->maxUses;
    }

    public function setMaxUses(?int $maxUses): static
    {
        $this->maxUses = $maxUses;

        return $this;
    }

    public function getMaxUsesPerUser(): ?int
    {
        return $this->maxUsesPerUser;
    }

    public function setMaxUsesPerUser(?int $maxUsesPerUser): static
    {
        $this->maxUsesPerUser = $maxUsesPerUser;

        return $this;
    }

    /**
     * @return Collection<int, Apartment>
     */
    public function getApartments(): Collection
    {
        return $this->apartments;
    }

    public function addApartment(Apartment $apartment): static
    {
        if (!$this->apartments->contains($apartment)) {
            $this->apartments->add($apartment);
        }

        return $this;
    }

    public function removeApartment(Apartment $apartment): static
    {
        $this->apartments->removeElement($apartment);

        return $this;
    }

    /**
     * @return Collection<int, CouponRedemption>
     */
    public function getRedemptions(): Collection
    {
        return $this->redemptions;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
