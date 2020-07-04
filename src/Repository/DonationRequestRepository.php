<?php

namespace App\Repository;

use App\Entity\DonationRequest;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method DonationRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method DonationRequest|null findOneBy(array $criteria, array $orderBy = null)
 * @method DonationRequest[]    findAll()
 * @method DonationRequest[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class DonationRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DonationRequest::class);
    }

    // /**
    //  * @return DonationRequest[] Returns an array of DonationRequest objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('d.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?DonationRequest
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
