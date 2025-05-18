<?php

namespace App\Controller;

use App\Repository\OrderRepository;
use App\Repository\LivresRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;


class AdminController extends AbstractController
{
    #[Route('/admin/dashboard', name: 'admin_dashboard')]
   public function dashboard(Request $request, OrderRepository $commandeRepo, LivresRepository $livreRepo): Response
{
    // Dates par défaut : 30 derniers jours
    $defaultStart = (new \DateTime('-30 days'))->format('Y-m-d');
    $defaultEnd = (new \DateTime())->format('Y-m-d');

    // Récupération des dates du formulaire
    $startDate = $request->query->get('start_date', $defaultStart);
    $endDate = $request->query->get('end_date', $defaultEnd);

    // Conversion en DateTime
    $start = \DateTime::createFromFormat('Y-m-d', $startDate);
    $end = \DateTime::createFromFormat('Y-m-d', $endDate);

    // Validation des dates
    if (!$start || !$end) {
        $start = new \DateTime($defaultStart);
        $end = new \DateTime($defaultEnd);
    }

    // Livre le plus vendu
    $livresVendus = $livreRepo->findLivresLesPlusVendus();
    
    // Commandes par période
    $commandesParPeriode = $commandeRepo->countCommandesParPeriode($start, $end);

    return $this->render('admin/dashboard.html.twig', [
        'livresVendus' => $livresVendus,
        'commandesParPeriode' => $commandesParPeriode,
        'start_date' => $start->format('Y-m-d'),
        'end_date' => $end->format('Y-m-d')
    ]);
}
}
