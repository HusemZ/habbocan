<?php

namespace App\Controller\App;

use App\Form\ForgotPasswordForm;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SecurityController extends AbstractController
{
    #[Route(path: '/giris-yap', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_homepage');
        }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('app/security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/cikis-yap', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route(path: '/sifremi-unuttum', name: 'app_forgot_password')]
    public function forgotPassword(
        Request $request,
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        HttpClientInterface $httpClient
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_homepage');
        }

        if ($request->isMethod('GET')) {
            $randomCode = 'HABBOCAN' . $this->generateRandomString(4);
            $session->set('reset_code', $randomCode);
        } else {
            $randomCode = $session->get('reset_code');
        }

        $form = $this->createForm(ForgotPasswordForm::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $resetCode = $session->get('reset_code');

            $apiUrl = 'https://www.habbo.com.tr/api/public/users?name=' . urlencode($username);
            try {
                $response = $httpClient->request('GET', $apiUrl);
                $apiResponse = $response->getContent();
            } catch (\Exception $e) {
                $apiResponse = false;
            }

            $motto = null;
            if ($apiResponse !== false) {
                $data = json_decode($apiResponse, true);
                $motto = $data['motto'] ?? null;
            }

            if ($motto !== $resetCode) {
                $this->addFlash('error', 'Habbo motto kodu eşleşmiyor! Lütfen mottounuzu kontrol edin.');
                return $this->render('app/security/forgot_password.html.twig', [
                    'form' => $form->createView(),
                    'reset_code' => $resetCode,
                ]);
            }

            $user = $userRepository->findOneBy(['username' => $username]);
            if (!$user) {
                $this->addFlash('error', 'Bu kullanıcı adına sahip bir hesap sistemde bulunamadı.');
                return $this->redirectToRoute('app_forgot_password');
            }

            $plainPassword = $form->get('plainPassword')->getData();
            $confirmPassword = $form->get('confirmPassword')->getData();

            if ($plainPassword !== $confirmPassword) {
                $this->addFlash('error', 'Şifreler eşleşmiyor.');
                return $this->render('app/security/forgot_password.html.twig', [
                    'form' => $form->createView(),
                    'reset_code' => $resetCode,
                ]);
            }

            $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
            $entityManager->flush();

            $this->addFlash('success', 'Şifreniz başarıyla yenilendi. Yeni şifrenizle giriş yapabilirsiniz.');

            return $this->redirectToRoute('app_login');

        }

        return $this->render('app/security/forgot_password.html.twig', [
            'form' => $form->createView(),
            'reset_code' => $randomCode,
        ]);
    }

    private function generateRandomString($length = 4): string
    {
        $characters = '0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[random_int(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
