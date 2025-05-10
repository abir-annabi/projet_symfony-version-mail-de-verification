<?php
// src/Security/UserChecker.php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;

class UserChecker implements UserCheckerInterface
{
    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        // Bloque la connexion si l'utilisateur n'est pas vérifié
        if (!$user->isVerified()) {
            throw new CustomUserMessageAuthenticationException(
                'Vous devez confirmer votre adresse email avant de pouvoir vous connecter.'
            );
        }
    }

    public function checkPostAuth(UserInterface $user): void
    {
        // Rien à faire ici (optionnel)
    }
}