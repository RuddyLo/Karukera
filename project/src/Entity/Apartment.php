<?php

namespace App\Entity;

use App\Repository\ApartmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity(repositoryClass: ApartmentRepository::class)]
class Apartment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?bool $is_active = null;

    #[ORM\Column]
    private ?bool $on_top = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\OneToMany(targetEntity: Image::class, mappedBy: 'apartment')]
    private Collection $images;

    #[ORM\ManyToMany(targetEntity: Equipment::class, inversedBy: 'apartments')]
    private Collection $equipments;

    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'appartments')]
    private Collection $reservations;

    #[ORM\Column(nullable: true)]
    private ?float $price = null;

    #[ORM\OneToMany(targetEntity: PricePeriod::class, mappedBy: 'apartment')]
    private Collection $pricePeriods;

    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'apartment')]
    private Collection $reviews;

 

    public function __construct()
    {
        $this->images = new ArrayCollection();
        $this->equipments = new ArrayCollection();
        $this->reservations = new ArrayCollection();
        $this->pricePeriods = new ArrayCollection();
        $this->reviews = new ArrayCollection();
    }

    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function isIsActive(): ?bool
    {
        return $this->is_active;
    }

    public function setIsActive(bool $is_active): static
    {
        $this->is_active = $is_active;

        return $this;
    }

    public function isIsFavorite(): ?bool
    {
        return $this->on_top;
    }

    public function setIsFavorite(bool $on_top): static
    {
        $this->on_top = $on_top;

        return $this;
    }

    public function getImageUrl(): ?string
    {
        return $this->imageUrl;
    }

    public function setImageUrl(?string $imageUrl): static
    {
        $this->imageUrl = $imageUrl;

        return $this;
    }

    /**
     * @return Collection<int, Image>
     */
    public function getImages(): Collection
    {
        return $this->images;
    }

    public function addImage(Image $image): static
    {
        if (!$this->images->contains($image)) {
            $this->images->add($image);
            $image->setApartment($this);
        }

        return $this;
    }

    public function removeImage(Image $image): static
    {
        if ($this->images->removeElement($image)) {
            // set the owning side to null (unless already changed)
            if ($image->getApartment() === $this) {
                $image->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Equipment>
     */
    public function getEquipments(): Collection
    {
        return $this->equipments;
    }

    public function addEquipment(Equipment $equipment): static
    {
        if (!$this->equipments->contains($equipment)) {
            $this->equipments->add($equipment);
        }

        return $this;
    }

    public function removeEquipment(Equipment $equipment): static
    {
        $this->equipments->removeElement($equipment);

        return $this;
    }

    /**
     * @return Collection<int, Reservation>
     */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setApartment($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            // set the owning side to null (unless already changed)
            if ($reservation->getApartment() === $this) {
                $reservation->setApartment(null);
            }
        }

        return $this;
    }

    public function getPrice(): ?float
    {
        return $this->price;
    }

    public function setPrice(?float $price): static
    {
        $this->price = $price;

        return $this;
    }

    /**
     * @return Collection<int, PricePeriod>
     */
    public function getPricePeriods(): Collection
    {
        return $this->pricePeriods;
    }

    public function addPricePeriod(PricePeriod $pricePeriod): static
    {
        if (!$this->pricePeriods->contains($pricePeriod)) {
            $this->pricePeriods->add($pricePeriod);
            $pricePeriod->setApartment($this);
        }

        return $this;
    }

    public function removePricePeriod(PricePeriod $pricePeriod): static
    {
        if ($this->pricePeriods->removeElement($pricePeriod)) {
            // set the owning side to null (unless already changed)
            if ($pricePeriod->getApartment() === $this) {
                $pricePeriod->setApartment(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setApartment($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getApartment() === $this) {
                $review->setApartment(null);
            }
        }

        return $this;
    }

   

    
}
