<?php

namespace App\Controller\App;

use App\Repository\UserRepository;
use App\Service\HabboApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
class ProfileController extends AbstractController
{
    private UserRepository $userRepository;
    private HabboApiService $habboApiService;

    public function __construct(UserRepository $userRepository, HabboApiService $habboApiService)
    {
        $this->userRepository = $userRepository;
        $this->habboApiService = $habboApiService;
    }

    #[Route('/', name: 'app_profile')]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        $user = $this->getUser();

        if (!$user) {
            throw $this->createNotFoundException('User not found');
        }

        $habboProfile = $this->habboApiService->getProfileByUsername($user->getUsername());

        return $this->render('app/profile/index.html.twig', [
            'user' => $user,
            'habboProfile' => $habboProfile,
        ]);
    }

    #[Route('/edit', name: 'app_profile_edit')]
    #[IsGranted('ROLE_USER')]
    public function edit(): Response
    {
        return $this->render('app/profile/edit.html.twig', [
            'user' => $this->getUser()
        ]);
    }
}
