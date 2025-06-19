<?php

namespace App\Controller\App;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ContactController extends AbstractController
{
    #[Route('/iletisim', name: 'app_contact')]
    public function index(): Response
    {
        $contactInfo = [
            'email' => 'iletisim@habbocan.com',
            'twitter' => '@habbocan_com'
        ];

        return $this->render('app/contact/contact.html.twig', [
            'page_title' => 'İletişim - Habbocan',
            'contact_info' => $contactInfo
        ]);
    }
}
