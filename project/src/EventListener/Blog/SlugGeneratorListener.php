<?php
// src/EventListener/Blog/SlugGeneratorListener.php
namespace App\EventListener\Blog;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\String\Slugger\SluggerInterface;

class SlugGeneratorListener
{
    public function __construct(private SluggerInterface $slugger)
    {
    }

    public function prePersist(LifecycleEventArgs $args): void
    {
        $this->generateSlug($args);
    }

    public function preUpdate(LifecycleEventArgs $args): void
    {
        $this->generateSlug($args);
    }

    private function generateSlug(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof Article && !$entity->getSlug()) {
            $entity->setSlug($this->slugger->slug($entity->getTitle())->lower());
        }

        if ($entity instanceof Category && !$entity->getSlug()) {
            $entity->setSlug($this->slugger->slug($entity->getName())->lower());
        }
    }
}