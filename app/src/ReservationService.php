<?php

namespace App\DataFixtures;

use App\Entity\Evenement;
use App\Entity\Salle;
use App\Entity\Site;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // ---- Sites & salles ----
        $bastia = new Site();
        $bastia->setNom('BDE Bastia')->setVille('Bastia');
        $manager->persist($bastia);

        $ajaccio = new Site();
        $ajaccio->setNom('BDE Ajaccio')->setVille('Ajaccio');
        $manager->persist($ajaccio);

        $sallesBastia = $this->creerSalles($bastia, [20, 12, 14, 14]);
        $sallesAjaccio = $this->creerSalles($ajaccio, [30, 20, 20, 14, 14, 14]);

        foreach ([...$sallesBastia, ...$sallesAjaccio] as $salle) {
            $manager->persist($salle);
        }

        // ---- Utilisateurs de démonstration ----
        $admin = new User();
        $admin->setEmail('admin@askalinata.fr')
            ->setNom('Admin')
            ->setPrenom('BDE')
            ->setRoles(['ROLE_ADMIN'])
            ->setPassword($this->passwordHasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        $eleve = new User();
        $eleve->setEmail('eleve@askalinata.fr')
            ->setNom('Dupont')
            ->setPrenom('Léa')
            ->setRoles(['ROLE_USER'])
            ->setPassword($this->passwordHasher->hashPassword($eleve, 'eleve1234'));
        $manager->persist($eleve);

        // ---- Quelques événements à venir ----
        $maintenant = new \DateTimeImmutable('tomorrow 14:00');

        $evenement1 = new Evenement();
        $evenement1->setTitre('Soirée d\'intégration')
            ->setDescription('Soirée de bienvenue pour les nouveaux élèves de l\'école MIRA.')
            ->setDateDebut($maintenant)
            ->setDateFin($maintenant->modify('+3 hours'))
            ->setSalle($sallesBastia[0]);
        $manager->persist($evenement1);

        $evenement2 = new Evenement();
        $debut2 = $maintenant->modify('+2 days');
        $evenement2->setTitre('Tournoi sportif inter-sites')
            ->setDescription('Tournoi amical entre les élèves de Bastia et d\'Ajaccio.')
            ->setDateDebut($debut2)
            ->setDateFin($debut2->modify('+4 hours'))
            ->setSalle($sallesAjaccio[0]);
        $manager->persist($evenement2);

        $evenement3 = new Evenement();
        $debut3 = $maintenant->modify('+5 days +1 hour');
        $evenement3->setTitre('Atelier CV & LinkedIn')
            ->setDescription('Atelier animé par le BDE pour préparer sa recherche de stage.')
            ->setDateDebut($debut3)
            ->setDateFin($debut3->modify('+2 hours'))
            ->setSalle($sallesBastia[2]);
        $manager->persist($evenement3);

        // ---- Événement extérieur (pas de salle, pas de limite de participants) ----
        $evenement4 = new Evenement();
        $debut4 = $maintenant->modify('+7 days');
        $evenement4->setTitre('Randonnée du BDE')
            ->setDescription('Randonnée ouverte à tous les élèves, aucune inscription préalable en nombre limité.')
            ->setDateDebut($debut4)
            ->setDateFin($debut4->modify('+5 hours'))
            ->setLieuExterieur('Sentier des Douaniers, Cap Corse');
        $manager->persist($evenement4);

        $manager->flush();
    }

    /**
     * @param int[] $capacites
     * @return Salle[]
     */
    private function creerSalles(Site $site, array $capacites): array
    {
        $salles = [];
        $compteur = 1;

        foreach ($capacites as $capacite) {
            $salle = new Salle();
            $salle->setNom('Salle ' . $compteur++)
                ->setCapacite($capacite)
                ->setSite($site);
            $salles[] = $salle;
        }

        return $salles;
    }
}
