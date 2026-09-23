<?php

namespace App\Controller;

use App\Entity\Reservation;
use App\Entity\User;
use App\Repository\ReservationRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class ReservationController extends AbstractController
{
    #[Route('/mes-reservations', name: 'app_mes_reservations')]
    public function index(ReservationRepository $reservationRepository): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        return $this->render('reservation/mes_reservations.html.twig', [
            'reservations' => $reservationRepository->findMesReservations($user),
        ]);
    }

    #[Route('/reservations/{id}/annuler', name: 'app_reservation_annuler', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function annuler(Reservation $reservation, Request $request, ReservationService $reservationService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if ($reservation->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if (!$this->isCsrfTokenValid('annuler' . $reservation->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('app_mes_reservations');
        }

        try {
            $reservationService->annuler($reservation);
            $this->addFlash('success', 'Votre réservation a été annulée, un email vous a été envoyé.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_mes_reservations');
    }
}
