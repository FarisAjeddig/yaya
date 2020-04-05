<?php

namespace App\Repository;

use App\Entity\TypeDoctor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method TypeDoctor|null find($id, $lockMode = null, $lockVersion = null)
 * @method TypeDoctor|null findOneBy(array $criteria, array $orderBy = null)
 * @method TypeDoctor[]    findAll()
 * @method TypeDoctor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TypeDoctorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TypeDoctor::class);
    }

    // /**
    //  * @return TypeDoctor[] Returns an array of TypeDoctor objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TypeDoctor
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
