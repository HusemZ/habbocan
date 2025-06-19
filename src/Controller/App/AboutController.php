<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AboutController extends AbstractController
{
    #[Route('/hakkimizda', name: 'app_about')]
    public function about(): Response
    {
        return $this->render('app/about/about.html.twig', [
            'page_title' => 'Habbo Nedir?',
        ]);
    }
}
