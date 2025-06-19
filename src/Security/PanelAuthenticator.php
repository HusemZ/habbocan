<?php

namespace App\Security;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

class PanelAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
{
    private UserRepository $userRepository;
    private UrlGeneratorInterface $urlGenerator;
    private EntityManagerInterface $entityManager;

    public function __construct(UserRepository $userRepository, UrlGeneratorInterface $urlGenerator, EntityManagerInterface $entityManager)
    {
        $this->userRepository = $userRepository;
        $this->urlGenerator = $urlGenerator;
        $this->entityManager = $entityManager;
    }

    public function start(Request $request, AuthenticationException $authException = null): Response
    {
        return new RedirectResponse($this->urlGenerator->generate('panel_login'));
    }

    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST')
            && $request->attributes->get('_route') === 'panel_login'
            && $request->request->has('username')
            && $request->request->has('panel_password');
    }

    public function authenticate(Request $request): Passport
    {
        $username = $request->request->get('username');
        $panelPassword = $request->request->get('panel_password');

        return new SelfValidatingPassport(
            new UserBadge($username, function ($userIdentifier) use ($panelPassword) {
                $user = $this->userRepository->findOneBy(['username' => $userIdentifier]);

                if (!$user || !$user->getPanelPassword() || !password_verify($panelPassword, $user->getPanelPassword())) {
                    throw new AuthenticationException('Geçersiz kullanıcı adı veya panel şifresi.');
                }

                if (!$user->isActive()) {
                    throw new AuthenticationException('Hesabınız aktif değil.');
                }

                if ($user->isBlocked()) {
                    throw new AuthenticationException('Hesabınız bloke edilmiştir.');
                }

                if ($user->isDeleted()) {
                    throw new AuthenticationException('Hesabınız silinmiştir.');
                }

                $allowedRoles = [
                    'ROLE_ADMIN', 'ROLE_DEVELOPER', 'ROLE_MODERATOR',
                    'ROLE_HEAD_EDITOR', 'ROLE_HEAD_ARCHITECT', 'ROLE_EDITOR'
                ];
                $hasAccess = false;
                foreach ($allowedRoles as $role) {
                    if (in_array($role, $user->getRoles(), true)) {
                        $hasAccess = true;
                        break;
                    }
                }
                if (!$hasAccess) {
                    throw new AuthenticationException('Panel erişimi için yetkiniz yok.');
                }

                if ($hasAccess && password_verify($panelPassword, $user->getPanelPassword())) {
                    $user->setLastLogin(new \DateTime());
                    $this->entityManager->flush();
                }

                return $user;
            })
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        return new RedirectResponse($this->urlGenerator->generate('panel_dashboard'));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $request->getSession()->set('panel_auth_error', $exception->getMessage());
        return new RedirectResponse($this->urlGenerator->generate('panel_login'));
    }
}
