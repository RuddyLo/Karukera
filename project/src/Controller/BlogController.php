<?php
// src/Controller/BlogController.php
namespace App\Controller;

use App\Entity\Blog\Article;
use App\Entity\Blog\Category;
use App\Repository\Blog\ArticleRepository;
use App\Repository\Blog\CategoryRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/blog')]
class BlogController extends AbstractController
{
    public function __construct(
        private ArticleRepository $articleRepository,
        private CategoryRepository $categoryRepository
    ) {}

    #[Route('/{_locale}/blog', name: 'blog_index', requirements: ['_locale' => 'fr|en'])]
    public function index(): Response
    {
        $articles = $this->articleRepository->findPublishedArticles();
        $categories = $this->categoryRepository->findAll();

        return $this->render('blog/index.html.twig', [
            'articles' => $articles,
            'categories' => $categories,
        ]);
    }

    #[Route('/{_locale}/blog/article/{slug}', name: 'blog_article', requirements: ['_locale' => 'fr|en'])]
    public function article(string $slug): Response
    {
        $article = $this->articleRepository->findPublishedBySlug($slug);

        if (!$article) {
            throw $this->createNotFoundException('Article non trouvé');
        }

        return $this->render('blog/articles.html.twig', [
            'article' => $article,
        ]);
    }

    #[Route('/{_locale}/blog/categorie/{slug}', name: 'blog_category', requirements: ['_locale' => 'fr|en'])]
    public function category(Category $category): Response
    {
        $articles = $this->articleRepository->findPublishedByCategory($category);

        return $this->render('blog/category.html.twig', [
            'category' => $category,
            'articles' => $articles,
        ]);
    }
}
