<?php

namespace App\Controller\Admin;

use App\Entity\Reservation;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;

class ReservationCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Reservation::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Réservation')
            ->setEntityLabelInPlural('Réservations')
            ->setDefaultSort(['dateReservation' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        // les réservations sont créées uniquement via le parcours élève (règles métier)
        return $actions->disable(Action::NEW);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('evenement')
            ->add('user')
            ->add('statut');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('user', 'Élève');
        yield AssociationField::new('evenement', 'Événement');
        yield ChoiceField::new('statut')
            ->setChoices([
                'Confirmée' => \App\Entity\StatutReservation::CONFIRMEE,
                'Annulée' => \App\Entity\StatutReservation::ANNULEE,
            ])
            ->renderAsBadges([
                'confirmee' => 'success',
                'annulee' => 'secondary',
            ]);
        yield DateTimeField::new('dateReservation', 'Réservée le')->hideOnForm();
        yield DateTimeField::new('dateAnnulation', 'Annulée le')->hideOnForm();
    }
}
