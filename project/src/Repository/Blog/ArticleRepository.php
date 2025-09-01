<?php
// src/Repository/Blog/ArticleRepository.php
namespace App\Repository\Blog;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ArticleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Article::class);
    }

    public function findPublishedArticles(int $limit = null): array
    {
        $qb = $this->createQueryBuilder('a')
            ->where('a.published = true')
            ->orderBy('a.publishedAt', 'DESC');

        if ($limit) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }

    public function findPublishedBySlug(string $slug): ?Article
    {
        return $this->createQueryBuilder('a')
            ->where('a.slug = :slug')
            ->andWhere('a.published = true')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findPublishedByCategory(Category $category): array
    {
        return $this->createQueryBuilder('a')
            ->where('a.category = :category')
            ->andWhere('a.published = true')
            ->orderBy('a.publishedAt', 'DESC')
            ->setParameter('category', $category)
            ->getQuery()
            ->getResult();
    }
}