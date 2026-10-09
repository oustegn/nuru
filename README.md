# Nuru — Plateforme de formation Symfony

Base de site **Symfony 7.4 LTS + Twig**, responsive et en français, pour un projet de formation en Guinée et en Afrique francophone. « Nuru » est un nom de travail remplaçable. Le design est éditorial, ivoire et émeraude.

## Versions et guides

La version actuelle comprend **MySQL + Doctrine**, les entités `Etablissement`, `Formation`, `ModuleFormation` et **`User`**, une connexion locale e-mail/mot de passe, les rôles cumulables apprenant/formateur/admin et un tableau de bord administratif protégé.

L’inscription publique apprenant/formateur/établissement et la vérification e-mail avec SMTP sont désormais disponibles ; consultez [le guide inscriptions et validation admin](docs/inscriptions-validation-email.md).

Pour votre poste de développement, suivez [le guide MySQL local](docs/mysql-local.md), puis [le guide utilisateurs et administration](docs/utilisateurs-admin.md). Ce dernier couvre aussi la mise à jour depuis la version MySQL précédente : la migration supplémentaire ne supprime pas les formations.

## Ce qui fonctionne réellement

Le catalogue propose recherche et filtres, fiches de formation, objectifs, modules, tarifs GNF et informations de session. `CATALOG_SOURCE=database` lit ces données depuis MySQL ; `CATALOG_SOURCE=demo` utilise explicitement les données PHP d’exemple. Aucun repli silencieux n’a lieu en cas de panne SQL.

Les comptes sont persistés dans la table MySQL `user`, indépendante du nom du site, avec e-mail unique normalisé et mot de passe haché. Pour mettre à jour le nom historique `nuru_user`, suivez [le guide de renommage](docs/renommage-table-user.md). Symfony Security applique les contrôles CSRF, la limitation des tentatives, les sessions et les permissions. L’administrateur dispose des compteurs SQL et de listes paginées utilisateurs/formations/établissements **en lecture seule**, et d’une interface dédiée d’approbation/refus des inscriptions formateurs et établissements. Les formateurs voient uniquement les formations reliées à leur compte. L’apprenant dispose de son profil réel, mais pas encore d’inscriptions ou de progression individuelle.

Les commandes `app:user:create`, `app:formation:create`, `app:formation:assign` et `app:database:seed` permettent de préparer votre base. La création de compte est interactive avec saisie masquée ; aucun compte ni mot de passe par défaut n’est livré.

**Ce n’est pas encore un LMS de production.** Les anciens espaces `/demo/*` illustrent les progrès, revenus, quiz et candidatures avec des données fictives. Le mini-quiz ne persiste pas de résultat. Aucun paiement, diplôme officiel, admission ou inscription effective n’est activé. Les contenus d’exemple ne sont pas des offres accréditées.

## Prérequis

PHP **8.2 minimum** (testé avec 8.3), Composer 2, extensions ctype, iconv, PDO MySQL, mbstring et XML/DOM ; **MySQL 8** pour la persistance et les comptes. Aucun serveur Node n’est nécessaire. Les visuels sont inclus dans le projet.

## Installation locale

Extrayez l’archive dans un nouveau dossier pour préserver vos propres changements. Depuis la racine :

```bash
composer install
```

Créez `.env` avec `APP_ENV=dev`, puis adaptez `.env.dev.example` en `.env.dev`. Renseignez votre secret et votre URL MySQL. Pour localhost HTTP, utilisez `SESSION_SECURE=0` et `SESSION_SAMESITE=lax`. La base et un utilisateur dédié doivent être créés comme indiqué dans le guide MySQL.

```bash
php bin/console cache:clear --env=dev
php bin/console doctrine:migrations:migrate --env=dev
php bin/console app:database:seed --env=dev
php bin/console app:user:create admin@nuru.local VotrePrenom VotreNom --role=ROLE_ADMIN --env=dev
php -S 127.0.0.1:3000 -t public router.php
```

La commande de création demande votre mot de passe en saisie masquée. Connectez-vous à **http://127.0.0.1:3000/connexion**. Le serveur PHP intégré est réservé au développement. Votre `.env` et `.env.dev` sont exclus de Git ; `.env.dev.local` est également exclu et prend priorité.

Pour explorer uniquement les pages publiques sans MySQL, copiez `.env.example` en `.env`, remplacez son secret et gardez `CATALOG_SOURCE=demo`. Les vrais comptes et espaces privés nécessitent MySQL, quel que soit le mode du catalogue.

## Pages

| Route | Contenu / protection |
| --- | --- |
| `/` | Accueil public |
| `/formations` | Catalogue, filtres GET |
| `/formations/{slug}` | Détail, vraie 404 si inconnue |
| `/etablissements` | Présentation éditoriale des partenaires |
| `/connexion` | Connexion locale, CSRF |
| `/inscription` | Choix du profil et formulaires publics |
| `/verification-email/renvoyer` | Renvoi limité de vérification |
| `/admin/inscriptions` | Examen, approbation/refus des formateurs et établissements |
| `/espace/etablissement` | Structure liée au représentant validé |
| `/mon-espace` | Redirection selon le rôle, compte connecté |
| `/espace/apprenant` | Profil réel, rôle apprenant/admin |
| `/espace/formateur` | Formations affectées au compte, rôle formateur/admin |
| `/admin` | Tableau de bord, rôle admin |
| `/admin/utilisateurs` | Comptes, recherche, pagination ; admin |
| `/admin/formations` | Formations et formateurs affectés ; admin |
| `/admin/etablissements` | Établissements persistés ; admin |
| `/demo/apprenant`, `/demo/formateur`, `/demo/etablissement` | Maquettes publiques, fictives et distinctes |
| `/deconnexion` | POST CSRF, pas de GET |
| `/_app/health` | Santé technique JSON du serveur |
| `/manus-routes.json` | Manifeste des 25 pages et motifs |

Les espaces privés sont `noindex` et `no-store`. Le serveur renvoie 403 pour un compte connecté qui n’a pas le rôle requis. Les démonstrations restent publiques et ne donnent aucun privilège.

## Structure et personnalisation

| Élément | Fichiers |
| --- | --- |
| Entités et relations MySQL | `src/Entity/` |
| Requêtes, recherche et pagination | `src/Repository/` |
| Adaptateur de catalogue Twig | `src/Service/Catalog.php` |
| Jeu fictif et source de l’import | `src/Service/DemoCatalog.php` |
| Connexion et contrôle des sessions | `src/Security/`, `config/packages/security.yaml` |
| Commandes de préparation | `src/Command/` |
| Tableau de bord administratif | `src/Controller/AdminController.php`, `templates/admin/` |
| Espaces connectés et connexion | `templates/account/`, `templates/security/` |
| Design system et responsive | `public/assets/css/app.css` |
| Marque et navigation | `templates/base.html.twig` |
| Interactions des maquettes | `public/assets/js/app.js` |
| Logo et visuels | `public/assets/images/`, `public/manus-storage/` |
| Schéma versionné | `migrations/` |

Les photographies générées sont des illustrations : elles ne représentent pas des partenaires identifiés. Leurs copies locales permettent un usage autonome hors de Manus. Fraunces et Plus Jakarta Sans sont chargées depuis Google Fonts, avec polices de repli ; elles peuvent être auto-hébergées.

Laissez `SITE_PUBLIC_ORIGIN` vide en local. Sur un domaine final, fournissez une origine HTTPS sans barre oblique finale. Les URL publiques ne sont pas dérivées du nom d’hôte interne. Pour un hébergement indépendant, configurez sitemap et robots et excluez les routes privées et de démonstration.

## Vérifications

```bash
composer validate --strict
php bin/console lint:yaml config --env=dev
php bin/console lint:twig templates --env=dev
php bin/console lint:container --env=dev
php bin/console doctrine:schema:validate --env=dev
php tests/check.php
php tests/persistence.php
```

Les tests HTTP nécessitent le serveur sur 3000 et le jeu d’exemple ; leur origine peut être changée avec `TEST_BASE_URL`. Le test de persistance crée ses objets dans une transaction puis effectue un rollback.

`tests/authentication.py` et son helper testent les permissions, CSRF, invalidation des sessions inactives et pagination avec plus de vingt entrées. Ils requièrent Python et `requests` et **refusent toute base dont le nom ne finit pas par `_test` ou `_validation`**. Ils nettoient seulement leurs propres objets temporaires. Consultez le guide utilisateurs avant leur utilisation.

## Conteneur et production ultérieure

Le `Dockerfile` prépare Apache + PHP 8.3, PDO MySQL, dépendances verrouillées et migrations. Il sert uniquement `public/`, respecte `PORT` et écoute sur 3000 par défaut. Aucune migration et aucun compte admin ne sont créés automatiquement au démarrage.

Fournissez un `APP_SECRET` stable, `DATABASE_URL` et `CATALOG_SOURCE=database` à l’exécution pour utiliser les comptes et le catalogue SQL. Pour un aperçu HTTPS intégré, les cookies utilisent explicitement `SESSION_SECURE=1` / `SESSION_SAMESITE=none`. En localhost HTTP, utilisez les valeurs locales, pas celles du conteneur HTTPS.

Les envois de vérification utilisent Symfony Mailer et votre MAILER_DSN, avec APP_PUBLIC_ORIGIN et MAILER_FROM explicites. Le transport null://null des exemples n’envoie rien et ne valide jamais une adresse.

Les sessions sont locales à l’instance ; prévoyez un stockage partagé avant un déploiement multi-instance. La base doit être accessible depuis l’hébergement : son `127.0.0.1` n’est pas votre poste. La construction Docker et la publication hébergée n’ont pas été effectuées dans cette livraison.

## Prochaines étapes métier

Il reste à implémenter les inscriptions, progressions, résultats d’évaluation, admissions, droits des établissements, stockage protégé des contenus, paiement mobile et webhooks, récupération de mot de passe et gestion web des comptes au-delà de la validation initiale. La validation académique et la délivrance des diplômes restent sous contrôle des établissements partenaires.

## État de livraison

**80 contrôles d’inscription, 60 contrôles HTTP du site, 9 contrôles de persistance et 90 contrôles HTTP de sécurité réussis** dans une base de validation MySQL. Les commandes de création masquée et d’affectation ont également été vérifiées. Le schéma est cohérent et les templates, YAML, PHP et injections passent leurs contrôles. Aucun compte de test ni administrateur par défaut n’est conservé dans les livrables.
