<?php
// src/Repository/Blog/CategoryRepository.php
namespace App\Repository\Blog;

use App\Entity\Blog\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Category::class);
    }

    public function findAllWithArticleCount(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.articles', 'a')
            ->addSelect('COUNT(a.id) as articleCount')
            ->groupBy('c.id')
            ->getQuery()
            ->getResult();
    }
}