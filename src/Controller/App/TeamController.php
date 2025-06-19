<?php

namespace App\Controller\App;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TeamController extends AbstractController
{
    private UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    #[Route('/ekip', name: 'app_team')]
    public function index(): Response
    {
        $users = $this->userRepository->findBy(['isActive' => true]);

        $teamGroups = [];

        foreach ($users as $user) {
            $roles = $user->getRoles();

            if (in_array('ROLE_ADMIN', $roles) || in_array('ROLE_DEVELOPER', $roles)) {
                $teamGroups['Yönetici'][] = $user;
            }

            if (in_array('ROLE_MODERATOR', $roles)) {
                $teamGroups['Moderatör'][] = $user;
            }

            if (in_array('ROLE_HEAD_EDITOR', $roles) || in_array('ROLE_EDITOR', $roles)) {
                $teamGroups['Haberci'][] = $user;
            }

            if (in_array('ROLE_HEAD_ARCHITECT', $roles) || in_array('ROLE_ARCHITECT', $roles)) {
                $teamGroups['Mimar'][] = $user;
            }

            if (in_array('ROLE_MOBINATOR', $roles)) {
                $teamGroups['Mobinatör'][] = $user;
            }

            if (in_array('ROLE_GRAPHICER', $roles)) {
                $teamGroups['Grafiker'][] = $user;
            }

            if (in_array('ROLE_HEAD_IN_GAME_HELPER', $roles) || in_array('ROLE_IN_GAME_HELPER', $roles)) {
                $teamGroups['Oyun İçi Destek'][] = $user;
            }
        }

        return $this->render('app/team/index.html.twig', [
            'team_groups' => $teamGroups,
            'page_title' => 'Ekip Üyeleri'
        ]);
    }
}
