<?php

namespace App\Controller\App;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly NewsRepository $newsRepository
    ) {
    }

    #[Route('/', name: 'app_homepage')]
    public function index(): Response
    {
        // En son eklenen 9 haber
        $latestNews = $this->newsRepository->findLatestNews(9);
        
        // Sabitlenmiş haberler
        $pinnedNews = $this->newsRepository->findPinnedNews();
        
        // Rozet kazanılabilen haberler
        $badgeNews = $this->newsRepository->createQueryBuilder('n')
            ->where('n.status = :status')
            ->andWhere('n.badgeAvailability = :badgeAvailability')
            ->andWhere('n.badgeCodes IS NOT NULL')
            ->setParameter('status', 'published')
            ->setParameter('badgeAvailability', 'available')
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults(6)
            ->getQuery()
            ->getResult();
            
        // Rozet kodlarını formatlama
        $formattedBadgeNews = [];
        foreach ($badgeNews as $news) {
            $badgeCodes = $news->getBadgeCodes();
            $formattedBadgeCodes = [];
            
            if ($badgeCodes) {
                $formattedBadgeCodes = array_map('trim', explode(',', $badgeCodes));
            }
            
            $formattedBadgeNews[] = [
                'news' => $news,
                'formattedBadgeCodes' => $formattedBadgeCodes
            ];
        }

        return $this->render('app/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'latest_news' => $latestNews,
            'pinned_news' => $pinnedNews,
            'badge_news' => $formattedBadgeNews
        ]);
    }
}
