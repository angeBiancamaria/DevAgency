# A Skalinata — Réservation d'événements

Application web responsive permettant aux élèves de l'école MIRA (sites de **Bastia** et **Ajaccio**) de s'inscrire, réserver et annuler des créneaux sur les événements du bureau des élèves « A Skalinata », sans conflit de réservation. Un back-office admin permet de gérer les événements et de consulter les participants, avec envoi automatique d'emails de confirmation/annulation.

Voir le cahier des charges pour le détail du périmètre, des règles métier (anti double-booking, annulation jusqu'à 1h avant, capacité max par salle) et le planning.

## Équipe

| Nom | Rôle |
|---|---|
| Ange | Chef de projet – Développeur back-end |
| Chems | Développeur back-end - Conception |
| Christian | Développeur back-end - Conception |
| Niels | Développeur front-end |
| Olivier | Développeur front-end |

## Stack technique

- **Backend** : Symfony 7.4 (LTS), PHP 8.3
- **ORM** : Doctrine (MySQL)
- **Frontend** : Twig + Bootstrap 5 (chargé via AssetMapper, pas de build Node nécessaire)
- **Base de données** : MySQL 8.4
- **Environnement local** : Laragon (fournit PHP, Composer, MySQL, phpMyAdmin, Mailpit)

Le code de l'application se trouve dans [`backend/`](backend).

## 1. Installer Laragon

Télécharge et installe Laragon depuis [laragon.org](https://laragon.org/download/) (Laragon Full ou Laragon Lite peu importe, on utilise son PHP/Composer/MySQL intégrés). Alternative en ligne de commande si tu as `winget` :

```bash
winget install --id LeNgocKhoa.Laragon
```

Une fois installé, **lance Laragon et clique sur "Start All"** pour démarrer Apache/MySQL. Au premier démarrage, Laragon initialise son dossier de données MySQL et son `php.ini` automatiquement.

### Ajouter PHP et Composer au PATH

Laragon ne modifie pas toujours le PATH système automatiquement. Dans Laragon : **Menu (clic droit sur le tray icon) → PHP → active la version voulue**, puis vérifie que `php` et `composer` fonctionnent dans un nouveau terminal :

```bash
php -v
composer -V
```

Si ça ne fonctionne pas, ajoute manuellement au PATH utilisateur (adapte le nom de version PHP à ce que tu as) :

```
C:\laragon\bin\php\php-8.3.33-Win32-vs16-x64
C:\laragon\bin\composer
```

> **Windows → variables d'environnement** : `Modifier les variables d'environnement système` → `Variables d'environnement` → sous "Variables utilisateur", éditer `Path` et ajouter les deux chemins ci-dessus. Redémarre ton terminal après.

### Vérifier les extensions PHP

Symfony a besoin de `openssl`, `mbstring`, `pdo_mysql`, `intl`, `curl`, `zip`. Si `composer install` se plaint d'une extension manquante (ex. `The openssl extension is required...`), ouvre le `php.ini` utilisé (`php --ini` te donne le chemin) et décommente (enlève le `;`) les lignes correspondantes :

```ini
extension_dir = "ext"
extension=curl
extension=fileinfo
extension=intl
extension=mbstring
extension=mysqli
extension=openssl
extension=pdo_mysql
extension=sodium
extension=zip
```

## 2. Cloner le projet

```bash
git clone git@github.com:angeBiancamaria/DevAgency.git
cd DevAgency
```

## 3. Installer les dépendances

```bash
cd backend
composer install
```

## 4. Configurer la base de données

Chaque développeur doit avoir sa propre config locale : **ne modifie pas `.env`**, crée un `.env.local` (ignoré par git) à côté :

```bash
# backend/.env.local
DATABASE_URL="mysql://root:@127.0.0.1:3306/a_skalinata?serverVersion=8.4.3&charset=utf8mb4"
```

Adapte l'utilisateur/mot de passe/port si ta config MySQL Laragon diffère (par défaut Laragon utilise `root` sans mot de passe sur le port `3306`).

Puis crée la base et lance les migrations :

```bash
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate
```

## 5. Lancer l'application

**Option recommandée — via Laragon (Apache virtual host) :**

Place (ou clone) le dossier du projet dans `C:\laragon\www\`, ou crée un lien symbolique vers `backend/public`, puis Laragon génère automatiquement `http://backend.test`. C'est la façon la plus fiable de servir l'app car AssetMapper (qui sert Bootstrap et le JS) a besoin qu'Apache route tout vers `public/index.php`.

**Option alternative — serveur PHP intégré :**

```bash
php -S 127.0.0.1:8000 -t public public/index.php
```

⚠️ Le paramètre `public/index.php` en 3ᵉ argument est indispensable : sans lui, les assets (CSS/JS Bootstrap servis dynamiquement par AssetMapper) renvoient une 404 et la page s'affiche sans style.

Ouvre ensuite `http://127.0.0.1:8000` (ou `http://backend.test`).

## Commandes utiles

```bash
php bin/console make:entity              # créer/modifier une entité
php bin/console make:migration           # générer une migration après modif des entités
php bin/console doctrine:migrations:migrate
php bin/console make:controller
php bin/console debug:router             # lister toutes les routes
php bin/console about                    # infos environnement (version Symfony, PHP, etc.)
```

## Workflow Git

- **Une branche par personne/tâche**, préfixée par ton prénom : `chems/...`, `ange/...`, `christian/...`, `niels/...`, `olivier/...` (ex. `chems/entites-modele-donnees`, `olivier/ui-reservation`).
- Commit régulièrement, messages clairs.
- Ouvre une Pull Request vers `main` pour la revue avant fusion — évite de push directement sur `main`.
- En cas de conflit sur `composer.lock` ou `symfony.lock`, relance `composer install` après le merge plutôt que de résoudre le conflit à la main.

### Si `git push` est rejeté avec `GH007` (email privé)

GitHub bloque le push si le commit utilise ton email réel et que l'option de confidentialité est activée sur ton compte. Configure ton email "noreply" GitHub en local :

```bash
git config user.email "TON_ID+TON_USERNAME@users.noreply.github.com"
```

(Trouve ton ID sur `https://api.github.com/users/TON_USERNAME`, champ `id`.)

## Périmètre fonctionnel (rappel)

- Gestion des 2 sites (Bastia, Ajaccio) et de leurs salles (avec capacités)
- Authentification/inscription élève
- Réservation : 1 créneau à la fois, annulation/réservation jusqu'à 1h avant l'événement
- Contrôle de la capacité max par salle (anti sur-réservation)
- Calendrier des événements (vue élève + vue admin)
- Emails automatiques (confirmation/annulation)
- Back-office admin (CRUD événements, liste des participants)

**Hors périmètre** : paiement en ligne, application mobile native, multi-rôles avancé, statistiques, gestion de location matérielle.
