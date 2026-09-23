<?php

namespace App\Entity;

use App\Repository\EvenementRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EvenementRepository::class)]
#[Assert\Callback('validerLieu')]
class Evenement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private ?string $titre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateDebut = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $dateFin = null;

    #[ORM\ManyToOne(targetEntity: Salle::class, inversedBy: 'evenements')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Salle $salle = null;

    /** Nom du lieu, uniquement pour un événement extérieur (pas de salle associée). */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $lieuExterieur = null;

    /** @var Collection<int, Reservation> */
    #[ORM\OneToMany(targetEntity: Reservation::class, mappedBy: 'evenement', orphanRemoval: true)]
    private Collection $reservations;

    public function __construct()
    {
        $this->reservations = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeImmutable
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeImmutable $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeImmutable
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeImmutable $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getSalle(): ?Salle
    {
        return $this->salle;
    }

    public function setSalle(?Salle $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getLieuExterieur(): ?string
    {
        return $this->lieuExterieur;
    }

    public function setLieuExterieur(?string $lieuExterieur): static
    {
        $this->lieuExterieur = $lieuExterieur;

        return $this;
    }

    /** Un événement extérieur n'a pas de salle : lieu libre, sans limite de participants. */
    public function isExterieur(): bool
    {
        return $this->salle === null;
    }

    /** Libellé du lieu, que l'événement soit en salle ou extérieur. */
    public function getLieuLabel(): string
    {
        if ($this->isExterieur()) {
            return $this->lieuExterieur ?? 'Lieu à confirmer';
        }

        return sprintf('%s — %s', $this->salle->getNom(), $this->salle->getSite()->getVille());
    }

    /** @return Collection<int, Reservation> */
    public function getReservations(): Collection
    {
        return $this->reservations;
    }

    public function addReservation(Reservation $reservation): static
    {
        if (!$this->reservations->contains($reservation)) {
            $this->reservations->add($reservation);
            $reservation->setEvenement($this);
        }

        return $this;
    }

    public function removeReservation(Reservation $reservation): static
    {
        if ($this->reservations->removeElement($reservation)) {
            if ($reservation->getEvenement() === $this) {
                $reservation->setEvenement(null);
            }
        }

        return $this;
    }

    /** Réservations encore actives (confirmées) sur cet événement. */
    public function getReservationsConfirmees(): Collection
    {
        return $this->reservations->filter(
            fn (Reservation $r) => $r->getStatut() === StatutReservation::CONFIRMEE
        );
    }

    /** Places restantes, ou null si l'événement est extérieur (pas de limite). */
    public function getPlacesRestantes(): ?int
    {
        if ($this->isExterieur()) {
            return null;
        }

        return max(0, $this->salle->getCapacite() - $this->getReservationsConfirmees()->count());
    }

    public function isComplet(): bool
    {
        return !$this->isExterieur() && $this->getPlacesRestantes() <= 0;
    }

    /** Représentation textuelle "places restantes / capacité", pour l'affichage (ex. EasyAdmin). */
    public function getPlacesRestantesLabel(): string
    {
        if ($this->isExterieur()) {
            return 'Illimité';
        }

        return sprintf('%d / %d', $this->getPlacesRestantes(), $this->salle->getCapacite());
    }

    /** Liste des participants confirmés, pour l'affichage admin (ex. EasyAdmin). */
    public function getParticipantsListe(): string
    {
        $noms = [];
        foreach ($this->reservations as $reservation) {
            if ($reservation->getStatut() === StatutReservation::CONFIRMEE) {
                $noms[] = $reservation->getUser()->getNomComplet() . ' (' . $reservation->getUser()->getEmail() . ')';
            }
        }

        return $noms === [] ? 'Aucun participant pour le moment.' : implode("\n", $noms);
    }

    public function __toString(): string
    {
        return $this->titre ?? '';
    }

    /** Un événement a soit une salle (intérieur), soit un lieu extérieur — jamais les deux, jamais aucun. */
    public function validerLieu(ExecutionContextInterface $context): void
    {
        if ($this->salle !== null && $this->lieuExterieur !== null && $this->lieuExterieur !== '') {
            $context->buildViolation('Choisissez soit une salle, soit un lieu extérieur, pas les deux.')
                ->atPath('lieuExterieur')
                ->addViolation();
        }

        if ($this->salle === null && ($this->lieuExterieur === null || $this->lieuExterieur === '')) {
            $context->buildViolation('Indiquez une salle ou un lieu extérieur.')
                ->atPath('lieuExterieur')
                ->addViolation();
        }
    }
}
