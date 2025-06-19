<?php

namespace App\Controller\App;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsController extends AbstractController
{
    private NewsRepository $newsRepository;
    private int $newsPerPage = 9;

    public function __construct(NewsRepository $newsRepository)
    {
        $this->newsRepository = $newsRepository;
    }

    #[Route('/haberler', name: 'app_news')]
    public function index(Request $request): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $categorySlug = $request->query->get('category');

        // Sabit kategori listesi
        $categories = [
            ['id' => 1, 'name' => 'Habbo', 'slug' => 'habbo'],
            ['id' => 2, 'name' => 'Habbocan', 'slug' => 'habbocan'],
            ['id' => 3, 'name' => 'Yol Haritaları', 'slug' => 'yol-haritalari'],
            ['id' => 4, 'name' => 'Etkinlikler', 'slug' => 'etkinlikler'],
            ['id' => 5, 'name' => 'Habbo Mağaza', 'slug' => 'habbo-magaza']
        ];

        // Aktif kategoriyi bul
        $activeCategoryName = null;
        if ($categorySlug) {
            foreach ($categories as $cat) {
                if ($cat['slug'] === $categorySlug) {
                    $activeCategoryName = $cat['name'];
                    break;
                }
            }
        }

        // Sorgu oluştur
        $queryBuilder = $this->newsRepository->createQueryBuilder('n')
            ->where('n.status = :status')
            ->setParameter('status', 'published')
            ->orderBy('n.isPinned', 'DESC')
            ->addOrderBy('n.createdAt', 'DESC');

        // Kategori filtresi
        if ($activeCategoryName) {
            $queryBuilder->andWhere('n.category = :category')
                ->setParameter('category', $activeCategoryName);
        }

        // Sayfalama
        $queryBuilder->setFirstResult(($page - 1) * $this->newsPerPage)
            ->setMaxResults($this->newsPerPage);

        $paginator = new Paginator($queryBuilder);
        $totalItems = count($paginator);
        $pageCount = ceil($totalItems / $this->newsPerPage);

        return $this->render('app/news/index.html.twig', [
            'news' => $paginator,
            'categories' => $categories,
            'active_category' => $activeCategoryName,
            'active_slug' => $categorySlug,
            'pagination' => [
                'currentPage' => $page,
                'pageCount' => $pageCount,
                'totalItems' => $totalItems,
            ],
        ]);
    }

    #[Route('/haberler/{slug}', name: 'app_news_show')]
    public function show(string $slug): Response
    {
        $article = $this->newsRepository->findOneBy(['slug' => $slug]);

        if (!$article) {
            throw $this->createNotFoundException('Haber bulunamadı');
        }

        // İlgili haberleri al (aynı kategoriden, fakat bu haber hariç)
        $relatedNews = $this->newsRepository->createQueryBuilder('n')
            ->where('n.category = :category')
            ->andWhere('n.id != :id')
            ->setParameter('category', $article->getCategory())
            ->setParameter('id', $article->getId())
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getResult();

        return $this->render('app/news/show.html.twig', [
            'article' => $article,
            'relatedNews' => $relatedNews,
        ]);
    }
}
