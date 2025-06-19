<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if ($user->isDeleted()) {
            throw new CustomUserMessageAuthenticationException('Hesabınız silinmiştir.');
        }

        if ($user->isBlocked()) {
            throw new CustomUserMessageAuthenticationException('Hesabınız bloke edilmiştir.');
        }

        if (!$user->isActive()) {
            throw new CustomUserMessageAuthenticationException('Hesabınız aktif değil.');
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // No post-auth checks needed
    }
}
