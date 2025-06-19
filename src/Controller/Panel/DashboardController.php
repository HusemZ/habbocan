<?php

namespace App\Controller\Panel;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class DashboardController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/panel', name: 'panel_dashboard')]
    public function index(): Response
    {

        if (!$this->getUser()) {
            return $this->redirectToRoute('app_homepage');
        }

        $userCount = $this->entityManager->getRepository(User::class)->count([]);

        return $this->render('panel/dashboard/index.html.twig', [
            'userCount' => $userCount,
        ]);
    }
}
