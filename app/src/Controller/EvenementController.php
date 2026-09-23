<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\User;
use App\Repository\EvenementRepository;
use App\Service\ReservationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class EvenementController extends AbstractController
{
    #[Route('/', name: 'app_accueil')]
    public function accueil(EvenementRepository $evenementRepository): Response
    {
        return $this->render('evenement/accueil.html.twig', [
            'evenements' => $evenementRepository->findAVenir(),
        ]);
    }

    #[Route('/calendrier', name: 'app_calendrier')]
    public function calendrier(): Response
    {
        return $this->render('evenement/calendrier.html.twig');
    }

    #[Route('/api/evenements', name: 'app_api_evenements', methods: ['GET'])]
    public function apiEvenements(EvenementRepository $evenementRepository): JsonResponse
    {
        $evenements = $evenementRepository->findAll();

        $data = array_map(static function (Evenement $evenement) {
            $complet = $evenement->isComplet();

            return [
                'id' => $evenement->getId(),
                'title' => $evenement->getTitre() . ($complet ? ' (complet)' : ''),
                'start' => $evenement->getDateDebut()->format(\DateTimeInterface::ATOM),
                'end' => $evenement->getDateFin()->format(\DateTimeInterface::ATOM),
                'url' => '/evenements/' . $evenement->getId(),
                'color' => $complet ? '#14141a' : ($evenement->isExterieur() ? '#2f9e44' : '#c8102e'),
            ];
        }, $evenements);

        return $this->json($data);
    }

    #[Route('/evenements/{id}', name: 'app_evenement_show', requirements: ['id' => '\d+'])]
    public function show(Evenement $evenement): Response
    {
        $reservationActive = null;

        /** @var User|null $user */
        $user = $this->getUser();
        if ($user) {
            foreach ($evenement->getReservationsConfirmees() as $reservation) {
                if ($reservation->getUser() === $user) {
                    $reservationActive = $reservation;
                    break;
                }
            }
        }

        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'reservationActive' => $reservationActive,
        ]);
    }

    #[Route('/evenements/{id}/reserver', name: 'app_evenement_reserver', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function reserver(Evenement $evenement, Request $request, ReservationService $reservationService): Response
    {
        if (!$this->isCsrfTokenValid('reserver' . $evenement->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de sécurité invalide, merci de réessayer.');

            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        /** @var User $user */
        $user = $this->getUser();

        try {
            $reservationService->reserver($user, $evenement);
            $this->addFlash('success', 'Votre réservation est confirmée, un email vous a été envoyé.');
        } catch (\DomainException $e) {
            $this->addFlash('error', $e->getMessage());
        }

        return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
    }
}
