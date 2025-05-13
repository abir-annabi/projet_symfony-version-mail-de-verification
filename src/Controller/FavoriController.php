<?php 
// src/Controller/FavoriController.php
// src/Controller/FavoriController.php

namespace App\Controller;

use App\Entity\Favori;
use App\Entity\Livre;  // Notez le 's' à Livres
use App\Repository\FavoriRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FavoriController extends AbstractController
{
    #[Route('/livre/{id}/favori', name: 'toggle_favori')]
    public function toggleFavori(Livre $livre, FavoriRepository $favoriRepo, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();
        
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $favori = $favoriRepo->findOneBy([
            'user' => $user, 
            'livre' => $livre
        ]);

        if ($favori) {
            $em->remove($favori);
            $this->addFlash('success', 'Livre retiré des favoris');
        } else {
            $favori = new Favori();
            $favori->setUser($user);
            $favori->setLivre($livre);
            $em->persist($favori);
            $this->addFlash('success', 'Livre ajouté aux favoris');
        }

        $em->flush();

        return $this->redirectToRoute('app_livre_all1');
    }
}