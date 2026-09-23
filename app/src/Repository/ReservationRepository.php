<?php

namespace App\Repository;

use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Entity\StatutReservation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Reservation> */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Vérifie que l'utilisateur n'a pas déjà une réservation confirmée
     * sur un créneau qui chevauche celui de l'événement visé.
     */
    public function existeChevauchement(User $user, Evenement $evenement): bool
    {
        $count = $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->join('r.evenement', 'e')
            ->andWhere('r.user = :user')
            ->andWhere('r.statut = :statut')
            ->andWhere('e.id != :evenementId')
            ->andWhere('e.dateDebut < :fin')
            ->andWhere('e.dateFin > :debut')
            ->setParameter('user', $user)
            ->setParameter('statut', StatutReservation::CONFIRMEE)
            ->setParameter('evenementId', $evenement->getId())
            ->setParameter('debut', $evenement->getDateDebut())
            ->setParameter('fin', $evenement->getDateFin())
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function trouverReservationActive(User $user, Evenement $evenement): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.user = :user')
            ->andWhere('r.evenement = :evenement')
            ->andWhere('r.statut = :statut')
            ->setParameter('user', $user)
            ->setParameter('evenement', $evenement)
            ->setParameter('statut', StatutReservation::CONFIRMEE)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function compterConfirmees(Evenement $evenement): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->andWhere('r.evenement = :evenement')
            ->andWhere('r.statut = :statut')
            ->setParameter('evenement', $evenement)
            ->setParameter('statut', StatutReservation::CONFIRMEE)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** @return Reservation[] */
    public function findMesReservations(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.evenement', 'e')
            ->andWhere('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('e.dateDebut', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
