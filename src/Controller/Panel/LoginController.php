<?php

namespace App\Controller\Panel;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class LoginController extends AbstractController
{
    #[Route('/panel/login', name: 'panel_login')]
    public function login(
        Request $request,
        AuthenticationUtils $authenticationUtils
    ): Response {
        if ($this->getUser() && in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('panel_dashboard');
        }

        $authError = $request->getSession()->get('panel_auth_error');
        $request->getSession()->remove('panel_auth_error');

        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('panel/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $authError
        ]);
    }


    #[Route('/panel/logout', name: 'panel_logout')]
    public function logout(Request $request): Response
    {
        $this->container->get('security.token_storage')->setToken(null);
        $request->getSession()->invalidate();

        $this->addFlash('success', 'Çıkış işlemi başarılı.');

        return $this->redirectToRoute('panel_login');
    }
}
