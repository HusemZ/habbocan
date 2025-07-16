<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PixelArtController extends AbstractController
{
    #[Route('/pixel-art', name: 'app_pixel_art')]
    public function index(): Response
    {
        return $this->render('app/pixel-art/index.html.twig');
    }
}
