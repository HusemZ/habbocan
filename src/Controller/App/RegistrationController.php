<?php

namespace App\Controller\App;

use App\Entity\User;
use App\Form\RegistrationForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RegistrationController extends AbstractController
{
    #[Route('/kayit-ol', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        HttpClientInterface $httpClient
    ): Response
    {
        if ($this->getUser()) {
            return $this->redirectToRoute('_profiler');
        }

        if ($request->isMethod('GET')) {
            $randomCode = 'HABBOCAN' . $this->generateRandomString(4);
            $session->set('register_code', $randomCode);
        } else {
            $randomCode = $session->get('register_code');
        }

        $user = new User();
        $form = $this->createForm(RegistrationForm::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $username = $form->get('username')->getData();
            $registerCode = $session->get('register_code');

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

            if ($motto !== $registerCode) {
                $this->addFlash('error', 'Habbo motto kodu eşleşmiyor!');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'register_code' => $registerCode,
                ]);
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('_profiler');
        }

        return $this->render('app/registration/register.html.twig', [
            'registrationForm' => $form,
            'register_code' => $randomCode,
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
