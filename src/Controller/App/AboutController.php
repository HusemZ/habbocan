<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HabboController extends AbstractController
{
    #[Route('/habbo-nedir', name: 'app_habbo_about')]
    public function about(): Response
    {
        return $this->render('app/habbo/habbo_nedir.html.twig', [
            'page_title' => 'Habbo Nedir?',
        ]);
    }

    #[Route('/habbo-kulubu', name: 'app_habbo_club')]
    public function club(): Response
    {
        return $this->render('app/habbo/habbo_kulubu.html.twig', [
            'page_title' => 'Habbo Kulübü',
        ]);
    }

    #[Route('/mimarlar-kulubu', name: 'app_habbo_builders_club')]
    public function buildersClub(): Response
    {
        return $this->render('app/habbo/mimarlar_kulubu.html.twig', [
            'page_title' => 'Mimarlar Kulübü',
        ]);
    }

    #[Route('/habbo-kredisi', name: 'app_habbo_credits')]
    public function credits(): Response
    {
        return $this->render('app/habbo/habbo_kredisi.html.twig', [
            'page_title' => 'Habbo Kredisi',
        ]);
    }

    #[Route('/habbo-elmasi', name: 'app_habbo_diamonds')]
    public function diamonds(): Response
    {
        return $this->render('app/habbo/habbo_elmasi.html.twig', [
            'page_title' => 'Habbo Elması',
        ]);
    }

    #[Route('/buyukelciler', name: 'app_habbo_ambassadors')]
    public function ambassadors(): Response
    {
        $ambassadors = [
            ['name' => 'doğanehir123', 'role' => 'Büyükelçi', 'start_date' => '29 Mayıs 2023'],
            ['name' => 'bektaş55', 'role' => 'Baş Büyükelçi', 'start_date' => '1 Temmuz 2024'],
            ['name' => 'Ahmet.Yiğit', 'role' => 'Büyükelçi', 'start_date' => '1 Temmuz 2024'],
            ['name' => 'opemirhanq03', 'role' => 'Büyükelçi', 'start_date' => '1 Temmuz 2024'],
        ];

        return $this->render('app/habbo/buyukelciler.html.twig', [
            'page_title' => 'Büyükelçiler',
            'ambassadors' => $ambassadors,
        ]);
    }

    #[Route('/yaratici-mimarlar', name: 'app_habbo_creative_builders')]
    public function creativeBuilders(): Response
    {
        return $this->render('app/habbo/yaratici_mimarlar.html.twig', [
            'page_title' => 'Yaratıcı Mimarlar',
        ]);
    }

    #[Route('/sponsorlu-oyunlar', name: 'app_habbo_sponsored_games')]
    public function sponsoredGames(): Response
    {
        $sponsoredGamesManager = [
            ['name' => 'Hanefi', 'start_date' => '30 Ocak 2025'],
            ['name' => 'superazadyusuf', 'start_date' => '28 Mayıs 2025'],
        ];

        $hostTeams = [
            [
                'member1' => ['name' => 'coolbayramg'],
                'member2' => ['name' => '5xEcex8']
            ],
            [
                'member1' => ['name' => 'ömer12345.'],
                'member2' => ['name' => 'AhmetCan']
            ],
            [
                'member1' => ['name' => 'ºErenK.º'],
                'member2' => ['name' => 'Bios']
            ],
            [
                'member1' => ['name' => 'Marceus'],
                'member2' => ['name' => 'Divisor']
            ]
        ];

        return $this->render('app/habbo/sponsorlu_oyunlar.html.twig', [
            'page_title' => 'Sponsorlu Oyunlar',
            'sponsoredGamesManager' => $sponsoredGamesManager,
            'hostTeams' => $hostTeams,
        ]);
    }

    #[Route('/fansiteler', name: 'app_habbo_fansites')]
    public function fansites(): Response
    {
        return $this->render('app/habbo/fansiteler.html.twig', [
            'page_title' => 'Fansiteler',
        ]);
    }
}
