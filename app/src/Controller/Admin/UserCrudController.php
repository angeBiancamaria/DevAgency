<?php

namespace App\Controller\Admin;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['dateInscription' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('prenom', 'Prénom');
        yield TextField::new('nom', 'Nom');
        yield EmailField::new('email', 'Email');

        yield ChoiceField::new('role', 'Rôle')
            ->setChoices([
                'Élève' => 'ROLE_USER',
                'Administrateur' => 'ROLE_ADMIN',
            ])
            ->renderAsBadges([
                'ROLE_USER' => 'secondary',
                'ROLE_ADMIN' => 'danger',
            ])
            ->setSortable(false);

        yield TextField::new('plainPassword', 'Mot de passe')
            ->setFormType(PasswordType::class)
            ->onlyOnForms()
            ->setHelp($pageName === Crud::PAGE_NEW
                ? 'Mot de passe initial de l\'utilisateur.'
                : 'Laissez vide pour ne pas changer le mot de passe.')
            ->setRequired($pageName === Crud::PAGE_NEW);

        yield DateTimeField::new('dateInscription', 'Inscrit le')
            ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPasswordIfProvided($entityInstance);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->hashPasswordIfProvided($entityInstance);
        parent::updateEntity($entityManager, $entityInstance);
    }

    private function hashPasswordIfProvided(mixed $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            return;
        }

        $plainPassword = $entityInstance->getPlainPassword();

        if (!empty($plainPassword)) {
            $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $plainPassword));
            $entityInstance->eraseCredentials();
        } elseif (empty($entityInstance->getPassword())) {
            // garde-fou : un utilisateur ne doit jamais être persisté sans mot de passe exploitable
            $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, bin2hex(random_bytes(16))));
        }
    }
}
