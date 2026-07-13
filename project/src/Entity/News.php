<?php

namespace App\Entity;

use App\Repository\NewsRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Gedmo\Translatable\Translatable;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
class News implements Translatable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Gedmo\Translatable]
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(nullable: true)]
    private ?bool $active = null;

    #[Gedmo\Locale]
    private ?string $translatableLocale = null;

    #[ORM\OneToMany(targetEntity: NewsImage::class, mappedBy: 'news')]
    private Collection $newsImages;

    public function __construct()
    {
        $this->newsImages = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function isActive(): ?bool
    {
        return $this->active;
    }

    public function setActive(?bool $active): static
    {
        $this->active = $active;

        return $this;
    }

    /**
     * @return Collection<int, NewsImage>
     */
    public function getNewsImages(): Collection
    {
        return $this->newsImages;
    }

    public function addNewsImage(NewsImage $newsImage): static
    {
        if (!$this->newsImages->contains($newsImage)) {
            $this->newsImages->add($newsImage);
            $newsImage->setNews($this);
        }

        return $this;
    }

    public function removeNewsImage(NewsImage $newsImage): static
    {
        if ($this->newsImages->removeElement($newsImage)) {
            // set the owning side to null (unless already changed)
            if ($newsImage->getNews() === $this) {
                $newsImage->setNews(null);
            }
        }

        return $this;
    }
    
    public function setTranslatableLocale(?string $locale): static
    {
        $this->translatableLocale = $locale;
        return $this;
    }
}
