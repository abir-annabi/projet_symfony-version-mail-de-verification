<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    //    /**
    //     * @return Order[] Returns an array of Order objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('o.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Order
    //    {
    //        return $this->createQueryBuilder('o')
    //            ->andWhere('o.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function countCommandesParPeriode(?\DateTimeInterface $start, ?\DateTimeInterface $end): array
{
    $qb = $this->createQueryBuilder('o')
        ->select('o.id, o.createdAt');

    if ($start) {
        $qb->andWhere('o.createdAt >= :start')
           ->setParameter('start', $start->format('Y-m-d 00:00:00'));
    }

    if ($end) {
        $qb->andWhere('o.createdAt <= :end')
           ->setParameter('end', $end->format('Y-m-d 23:59:59'));
    }

    $results = $qb->orderBy('o.createdAt', 'ASC')
                 ->getQuery()
                 ->getResult();

    // Regrouper par jour
    $grouped = [];
    foreach ($results as $row) {
        $date = $row['createdAt']->format('Y-m-d');
        $grouped[$date] = ($grouped[$date] ?? 0) + 1;
    }

    // Formatage pour le graphique
    return array_map(function($date, $count) {
        return [
            'date' => (new \DateTime($date))->format('d/m/Y'), // Format français
            'total' => $count
        ];
    }, array_keys($grouped), $grouped);
}

}
