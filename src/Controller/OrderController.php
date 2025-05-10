<?php

namespace App\Controller;

use Stripe\Stripe;
use App\Entity\User;
use App\Entity\Order;

use App\Service\OrderMailer;
use Stripe\Checkout\Session;
use App\Service\OrderManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;

final class OrderController extends AbstractController
{
    
    #[Route('/checkout', name: 'order_checkout', methods: ['GET', 'POST'])]
public function checkout(OrderManager $orderManager, Request $request): Response
{
    $user = $this->getUser();

    if (!$user instanceof User) {
        $this->addFlash('error', 'Vous devez être connecté pour valider votre commande.');
        return $this->redirectToRoute('app_login');
    }

    $panier = $user->getPanier();

    if (!$panier || $panier->getPanierItems()->isEmpty()) {
        $this->addFlash('error', 'Votre panier est vide.');
        return $this->redirectToRoute('panier_index');
    }

    $order = $orderManager->createOrderFromCart($user, $panier);

    return $this->redirectToRoute('order_payment', ['id' => $order->getId()]);
}

public function payment(Order $order) {
    Stripe::setApiKey($this->getParameter('STRIPE_SECRET_KEY'));

    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => [
            [
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Commande #' . $order->getReference(),
                    ],
                    'unit_amount' => $order->getTotal() * 100, // Stripe utilise les centimes
                ],
                'quantity' => 1,
            ]
        ],
        'mode' => 'payment',
        'success_url' => $this->generateUrl('order_success', ['id' => $order->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
        
    ]);

    return $this->redirect($session->url);
}


public function success(
    Order $order, 
    OrderMailer $mailer,
    EntityManagerInterface $em
) {
    $order->setStatus('paid');
    $em->flush();
    
    $mailer->sendConfirmation($order);
    
    return $this->render('order/success.html.twig', ['order' => $order]);
}
#[Route('/order/history', name: 'order_history')]
public function history(EntityManagerInterface $em): Response
{
    /** @var User $user */
    $user = $this->getUser();
    
    // Tri par date décroissante
    $orders = $em->getRepository(Order::class)->findBy(
        ['user' => $user],
        ['createdAt' => 'DESC']
    );

    return $this->render('order/history.html.twig', [
        'orders' => $orders
    ]);
}
#[Route('/order/{id}', name: 'order_show')]
public function show(Order $order): Response
{
    // Vérifie que l'utilisateur peut voir cette commande
    $user = $this->getUser();
    if ($order->getUser() !== $user && !$this->isGranted('ROLE_ADMIN')) {
        throw $this->createAccessDeniedException('Vous ne pouvez pas accéder à cette commande');
    }

    return $this->render('order/show.html.twig', [
        'order' => $order
    ]);
}


}
