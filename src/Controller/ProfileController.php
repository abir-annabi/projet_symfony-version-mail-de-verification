<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProfileController extends AbstractController{

    #[Route('/profile', name: 'app_profile')]
public function profile(): Response
{
    // La vue affichera automatiquement les infos de l'utilisateur connecté via app.user
    return $this->render('profile/index.html.twig');
}
}
