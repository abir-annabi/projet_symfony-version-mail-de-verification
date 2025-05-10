<?php
// src/Service/OrderMailer.php
namespace App\Service;


use App\Entity\Order;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;

class OrderMailer {
    public function __construct(private MailerInterface $mailer) {}

    public function sendConfirmation(Order $order) {
        $email = (new Email())
            ->from('no-reply@monsite.com')
            ->to($order->getUser()->getEmail())
            ->subject('Confirmation de commande #' . $order->getReference())
            ->html("<h1>Merci pour votre commande !</h1><p>Total : {$order->getTotal()} €</p>");

        $this->mailer->send($email);
    }
}