<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CareerController extends AbstractController
{
    #[Route('/kariyer', name: 'app_career')]
    public function index(): Response
    {
        return $this->render('app/career/career.html.twig', [
            'page_title' => 'Habbocan\'da Kariyer',
            'application_email' => 'kariyer@habbocan.com'
        ]);
    }
}
