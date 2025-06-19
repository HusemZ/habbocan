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
            'isOwnProfile' => true
        ]);
    }

    #[Route('/{username}', name: 'app_profile_view', priority: -1)]
    public function viewProfile(string $username): Response
    {
        $profileUser = $this->userRepository->findOneBy(['username' => $username]);
        
        if (!$profileUser) {
            throw $this->createNotFoundException('Kullanıcı bulunamadı');
        }
        
        $currentUser = $this->getUser();
        $isOwnProfile = false;
        
        if ($currentUser && $currentUser->getUsername() === $username) {
            $isOwnProfile = true;
        }
        
        $habboProfile = $this->habboApiService->getProfileByUsername($username);
        
        return $this->render('app/profile/index.html.twig', [
            'user' => $profileUser,
            'habboProfile' => $habboProfile,
            'isOwnProfile' => $isOwnProfile,
            'currentUser' => $currentUser
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
