<?php
namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Entity\Panier;
use App\Entity\PanierItem;
use Doctrine\ORM\EntityManagerInterface;

class OrderManager {
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function createOrderFromCart(User $user, Panier $panier): Order {
        $order = new Order();
        $order
            ->setUser($user)
            ->setCreatedAt(new \DateTimeImmutable())
            ->setStatus('pending')
            ->setReference(uniqid('CMD_'));

        $total = 0;

        foreach ($panier->getPanierItems() as $panierItem) {
            $orderItem = new OrderItem();
            $orderItem
                ->setLivre($panierItem->getLivre())
                ->setQuantity($panierItem->getQuantity())
                ->setPrice($panierItem->getLivre()->getPrix())
                ->setOrderRelation($order);

            $total += $panierItem->getLivre()->getPrix() * $panierItem->getQuantity();
            $this->em->persist($orderItem);
        }

        $order->setTotal($total);
        $this->em->persist($order);
        $this->em->flush();

        return $order;
    }
}