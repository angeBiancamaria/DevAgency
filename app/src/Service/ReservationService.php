<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\Reservation;
use App\Entity\StatutReservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class ReservationService
{
    private const DELAI_MINIMUM = '1 hour';
    private const EMAIL_EXPEDITEUR = 'reservations@askalinata.fr';
    private const NOM_EXPEDITEUR = 'A Skalinata';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ReservationRepository $reservationRepository,
        private readonly MailerInterface $mailer,
    ) {
    }

    /** @throws \DomainException si une règle de gestion empêche la réservation */
    public function reserver(User $user, Evenement $evenement): Reservation
    {
        $maintenant = new \DateTimeImmutable();
        $limite = $evenement->getDateDebut()->modify('-' . self::DELAI_MINIMUM);

        if ($maintenant > $limite) {
            throw new \DomainException("La réservation n'est plus possible à moins d'1h du début de l'événement.");
        }

        if ($this->reservationRepository->trouverReservationActive($user, $evenement)) {
            throw new \DomainException('Vous avez déjà une réservation sur cet événement.');
        }

        if ($this->reservationRepository->existeChevauchement($user, $evenement)) {
            throw new \DomainException('Vous avez déjà une réservation sur un autre événement au même créneau horaire.');
        }

        if (!$evenement->isExterieur()) {
            $placesPrises = $this->reservationRepository->compterConfirmees($evenement);
            if ($placesPrises >= $evenement->getSalle()->getCapacite()) {
                throw new \DomainException('Cet événement a atteint sa capacité maximale.');
            }
        }

        $reservation = new Reservation();
        $reservation->setUser($user);
        $reservation->setEvenement($evenement);

        $this->entityManager->persist($reservation);
        $this->entityManager->flush();

        $this->envoyerEmail($reservation, confirmation: true);

        return $reservation;
    }

    /** @throws \DomainException si une règle de gestion empêche l'annulation */
    public function annuler(Reservation $reservation): void
    {
        if ($reservation->getStatut() === StatutReservation::ANNULEE) {
            throw new \DomainException('Cette réservation est déjà annulée.');
        }

        $maintenant = new \DateTimeImmutable();
        $limite = $reservation->getEvenement()->getDateDebut()->modify('-' . self::DELAI_MINIMUM);

        if ($maintenant > $limite) {
            throw new \DomainException("L'annulation n'est plus possible à moins d'1h du début de l'événement.");
        }

        $reservation->marquerAnnulee();
        $this->entityManager->flush();

        $this->envoyerEmail($reservation, confirmation: false);
    }

    private function envoyerEmail(Reservation $reservation, bool $confirmation): void
    {
        $user = $reservation->getUser();

        $email = (new TemplatedEmail())
            ->from(new Address(self::EMAIL_EXPEDITEUR, self::NOM_EXPEDITEUR))
            ->to(new Address($user->getEmail(), $user->getNomComplet()))
            ->subject($confirmation
                ? 'Confirmation de votre réservation — ' . $reservation->getEvenement()->getTitre()
                : 'Annulation de votre réservation — ' . $reservation->getEvenement()->getTitre())
            ->htmlTemplate($confirmation ? 'emails/confirmation.html.twig' : 'emails/annulation.html.twig')
            ->context(['reservation' => $reservation]);

        $this->mailer->send($email);
    }
}
