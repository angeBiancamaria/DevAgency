<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Evenement> */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

    /** @return Evenement[] */
    public function findAVenir(): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateFin >= :now')
            ->setParameter('now', new \DateTimeImmutable())
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return Evenement[] Événements dont le créneau croise [debut, fin]. */
    public function findChevauchant(\DateTimeImmutable $debut, \DateTimeImmutable $fin): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.dateDebut < :fin')
            ->andWhere('e.dateFin > :debut')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }
}
