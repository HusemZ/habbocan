<?php

namespace App\Controller\App;

use App\Repository\NewsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\HabboBadgeApiService;

final class HomeController extends AbstractController
{
    public function __construct(
        private readonly NewsRepository $newsRepository,
        private readonly \App\Repository\CommentRepository $commentRepository
    ) {
    }

    #[Route('/', name: 'app_homepage')]
    public function index(HabboBadgeApiService $badgeApiService): Response
    {
        $latestNews = $this->newsRepository->findLatestNews(9);
        
        $pinnedNews = $this->newsRepository->findPinnedNews();
        
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

        $latestStaffBadges = $badgeApiService->getLatestStaffBadges(12);

        // Son 10 onaylı yorum
        $recentComments = $this->commentRepository->findRecentComments(10);
        // Relative time hesapla
        $now = new \DateTimeImmutable();
        foreach ($recentComments as $comment) {
            $diff = $now->getTimestamp() - $comment->getCreatedAt()->getTimestamp();
            if ($diff < 60) {
                $comment->relativeTime = $diff . ' saniye önce';
            } elseif ($diff < 3600) {
                $min = floor($diff / 60);
                $comment->relativeTime = $min . ' dakika önce';
            } elseif ($diff < 86400) {
                $hour = floor($diff / 3600);
                $comment->relativeTime = $hour . ' saat önce';
            } elseif ($diff < 2592000) {
                $day = floor($diff / 86400);
                $comment->relativeTime = $day . ' gün önce';
            } elseif ($diff < 31536000) {
                $month = floor($diff / 2592000);
                $comment->relativeTime = $month . ' ay önce';
            } else {
                $year = floor($diff / 31536000);
                $comment->relativeTime = $year . ' yıl önce';
            }
        }

        return $this->render('app/home/index.html.twig', [
            'controller_name' => 'HomeController',
            'latest_news' => $latestNews,
            'pinned_news' => $pinnedNews,
            'badge_news' => $formattedBadgeNews,
            'latest_staff_badges' => $latestStaffBadges,
            'recent_comments' => $recentComments
        ]);
    }
}
