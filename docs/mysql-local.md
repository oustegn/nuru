# Nuru — MySQL local et persistance Doctrine

Cette version ajoute une vraie persistance MySQL au template Symfony. Le catalogue peut désormais lire les formations dans la base, et non seulement dans un tableau PHP. Ce guide porte sur le noyau pédagogique : **Etablissement**, **Formation** et **ModuleFormation**. La nouvelle extension ajoute aussi **User**, la connexion et les rôles ; son guide est disponible en fin de document.

Les tests ont été exécutés sur **MySQL 8.0.46, PHP 8.3 et Symfony 7.4** dans le sandbox. La création de la base sur votre poste reste à effectuer : ce guide ne suppose pas que Manus a accès à votre MySQL local.

## 1. Utiliser la nouvelle version du projet

Téléchargez l’archive `nuru-symfony-utilisateurs-admin.zip` fournie avec cette livraison. Décompressez-la dans un **nouveau dossier** pour ne pas écraser vos personnalisations ou votre configuration locale actuelle. Si vous avez déjà modifié Nuru, reportez les changements avec votre outil de comparaison ou Git plutôt que de remplacer tout votre dossier.

La nouvelle archive contient les dépendances déclarées et verrouillées, les entités, leurs repositories, les commandes et la migration initiale. Vous ne devez pas relancer `composer require` pour installer Doctrine : exécutez simplement, à la racine :

```bash
composer install
composer check-platform-reqs
```

Prérequis : PHP 8.2 minimum, recommandé 8.3 ; Composer 2 ; **extension PHP `pdo_mysql`** ; serveur MySQL 8 en fonctionnement. Si XAMPP ou WampServer fournit MariaDB à la place de MySQL, ne renseignez pas une fausse version MySQL : la configuration et la compatibilité doivent être adaptées à votre serveur réel.

Vérifiez le PHP utilisé en ligne de commande :

```bash
php -v
php --ini
php -m
```

`pdo_mysql` doit apparaître dans la liste des extensions. Le `php.ini` affiché est celui à adapter, pas nécessairement celui d’Apache. Sous Ubuntu 24.04, les composants peuvent être installés avec :

```bash
sudo apt update
sudo apt install mysql-server php8.3-mysql
sudo service mysql start
```

Sous Windows, vous pouvez utiliser le serveur MySQL déjà présent dans votre environnement de développement ou installer MySQL Server. Le serveur MySQL doit écouter sur le port choisi, **3306 dans les exemples**.

## 2. Créer une base et un utilisateur dédiés

Ouvrez le client MySQL avec un compte administrateur :

```bash
mysql -u root -p
```

Sur Ubuntu, si `root` utilise l’authentification par socket :

```bash
sudo mysql
```

Dans le client MySQL, exécutez :

```sql
SELECT VERSION();

CREATE DATABASE IF NOT EXISTS nuru
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

CREATE USER 'nuru_app'@'127.0.0.1'
    IDENTIFIED BY 'REMPLACEZ_PAR_UN_MOT_DE_PASSE_SOLIDE';

GRANT ALL PRIVILEGES ON nuru.*
    TO 'nuru_app'@'127.0.0.1';

EXIT;
```

Remplacez la chaîne de mot de passe **avant** d’exécuter `CREATE USER`. Un mot de passe aléatoire hexadécimal est facile à utiliser dans une URL sans caractères réservés. Vous pouvez en générer un avec `php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"` dans votre terminal, puis le conserver localement.

Le compte n’obtient des droits que sur **`nuru`**, pas sur toutes vos bases. Ces droits lui permettent d’appliquer les migrations sur votre poste de développement. En production, séparez idéalement le compte de migration du compte applicatif plus restreint.

Si `nuru_app` existe déjà, **ne le supprimez pas et ne changez pas son mot de passe à l’aveugle** : utilisez ses identifiants existants ou créez un nouvel utilisateur dédié. Si `nuru` contient déjà des tables de même nom, ne lancez pas la migration initiale sans examiner le schéma et sauvegarder les données ; utilisez de préférence une base neuve.

Testez la connexion TCP de votre utilisateur :

```bash
mysql -h 127.0.0.1 -P 3306 -u nuru_app -p nuru
```

## 3. Configurer `.env.dev`

Symfony conserve un fichier de base `.env` qui sélectionne l’environnement. Si vous n’avez pas encore ce fichier, créez-le à la racine avec :

```dotenv
APP_ENV=dev
```

Créez ensuite `.env.dev`. Vous pouvez partir de `.env.dev.example` :

**PowerShell :**

```powershell
Copy-Item .env.dev.example .env.dev
```

**Linux / macOS :**

```bash
cp .env.dev.example .env.dev
```

N’exécutez pas cette copie par-dessus une configuration existante sans en conserver une copie.

Renseignez vos valeurs dans `.env.dev` :

```dotenv
APP_DEBUG=0
APP_SECRET=REMPLACEZ_PAR_VOTRE_SECRET_SYMFONY
CATALOG_SOURCE=database
SITE_PUBLIC_ORIGIN=
DATABASE_URL="mysql://nuru_app:VOTRE_MOT_DE_PASSE@127.0.0.1:3306/nuru?serverVersion=8.0.46&charset=utf8mb4"
```

Remplacez les deux valeurs indiquées. Pour générer `APP_SECRET` :

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

**`serverVersion=8.0.46` n’est qu’un exemple** : utilisez la version réelle retournée par `SELECT VERSION()`, par exemple `8.4.0` si votre serveur est MySQL 8.4. Si votre mot de passe contient `@`, `#`, `%`, `:`, `/` ou d’autres caractères réservés d’URL, encodez sa composante de mot de passe ; un mot de passe hexadécimal évite cette difficulté.

Dans cette version, `.env` et `.env.dev` sont exclus de Git. Vous pouvez aussi conserver les secrets dans `.env.dev.local`, également exclu : ses valeurs remplacent celles de `.env.dev`. Attention à un ancien `.env.dev.local` qui pourrait encore pointer vers une autre base.

Le fichier `.env.dev.example` est le modèle partageable ; il ne doit jamais contenir vos vrais identifiants. Les variables déjà définies dans votre terminal sont prioritaires sur les fichiers Dotenv. Une configuration compilée dans `.env.local.php` peut aussi court-circuiter la lecture des fichiers : ne conservez pas un ancien dump de production pour ce développement local.

## 4. Créer les tables via la migration livrée

Toujours à la racine du projet :

```bash
php bin/console about
php bin/console cache:clear --env=dev
php bin/console doctrine:migrations:migrate --env=dev
php bin/console doctrine:schema:validate --env=dev
```

`about` doit indiquer l’environnement **dev**. Vérifiez la base cible avant de confirmer la migration. Celle-ci crée les tables suivantes :

| Table | Données et relations |
| --- | --- |
| `etablissement` | Nom, slug unique, date de création |
| `formation` | Titre, slug unique, domaine, programme, modalité, description, durée, prix GNF entier, objectifs JSON, informations de session ; FK vers l’établissement |
| `module_formation` | Titre, position, FK vers la formation ; position unique dans chaque formation |
| `doctrine_migration_versions` | Historique technique des migrations exécutées |

Les formations ont une relation **ManyToOne** vers un établissement. Les modules sont liés à une formation, persistés en cascade et ordonnés par position. La suppression d’un établissement encore référencé est bloquée. Une suppression réelle de formation peut supprimer ses modules : ne l’utilisez pas sans maîtriser les conséquences.

La migration initiale est déjà fournie ; la version courante inclut aussi les migrations pour les utilisateurs et l’affectation des formateurs ; la table finale des comptes s’appelle `user` après renommage du nom historique `nuru_user`. **N’exécutez pas `make:migration` pour refaire le même schéma lors de la première installation.** Les relances de `doctrine:migrations:migrate` ne rejouent pas une migration enregistrée comme terminée.

MySQL effectue des commits implicites pour le DDL : une migration de schéma n’est pas une sauvegarde ni une garantie de rollback de toutes les instructions si elle échoue à mi-chemin.

## 5. Importer les formations d’exemple

```bash
php bin/console app:database:seed --env=dev
```

Sur une base neuve, cette commande ajoute **3 établissements, 7 formations et 33 modules**. Les contenus restent fictifs.

L’import est **additif** : il ignore les formations dont le slug est déjà présent et ne remplace pas vos titres, prix ou modules. Il ne vide aucune table. Vous pouvez le relancer ; une deuxième exécution après l’import initial doit annoncer `0 formations ajoutées ; 7 déjà présentes et laissées intactes`.

Une formation d’exemple supprimée peut être réajoutée lors d’une nouvelle exécution : cette commande sert à initialiser la démo, pas à synchroniser automatiquement vos contenus réels.

## 6. Lire le catalogue depuis MySQL

Avec `CATALOG_SOURCE=database`, lancez :

```bash
php -S 127.0.0.1:3000 -t public router.php
```

Ouvrez **http://127.0.0.1:3000/formations**. Les formations, objectifs, modules, prix et noms d’établissement affichés proviennent de MySQL. Le même catalogue alimente l’accueil et les cartes utilisées dans les espaces de démonstration.

Il n’y a **pas de repli silencieux** sur les tableaux de démonstration si MySQL tombe en panne. Pour utiliser volontairement le template sans connexion à la base, renseignez `CATALOG_SOURCE=demo` puis redémarrez le serveur. Les indicateurs métier et profils des tableaux de bord restent illustratifs dans les deux modes ; la page de présentation des établissements reste éditoriale, pas un annuaire CRUD.

## 7. Persister une nouvelle formation dès maintenant

Après l’import initial, exécutez :

```bash
php bin/console app:formation:create initiation-php "Initiation à PHP" nuru-digital-academy --prix=250000 --env=dev
```

Cette commande utilise un établissement existant, contrôle le slug, le titre et le prix, appelle `persist()` puis `flush()`, et affiche l’identifiant généré. Elle ne remplace pas une formation de même slug.

La formation est ensuite visible à :

**http://127.0.0.1:3000/formations/initiation-php**

Les autres champs utilisent des valeurs initiales à compléter. La commande de création ne crée pas automatiquement des modules ni des objectifs ; ces contenus peuvent être ajoutés via les entités Doctrine. Il n’y a pas encore de formulaire d’administration web.

### Exemple de service pour créer une formation avec un module

Le code suivant illustre le service à ajouter à votre application. Il n’expose volontairement aucune route publique d’écriture sans authentification.

```php
<?php

namespace App\Service;

use App\Entity\Formation;
use App\Entity\ModuleFormation;
use App\Repository\EtablissementRepository;
use Doctrine\ORM\EntityManagerInterface;

final class FormationWriter
{
    public function __construct(
        private EntityManagerInterface $em,
        private EtablissementRepository $etablissements,
    ) {}

    public function creer(string $slug, string $titre): Formation
    {
        $etablissement = $this->etablissements->findOneBy([
            'slug' => 'nuru-digital-academy',
        ]);
        if ($etablissement === null) {
            throw new \RuntimeException('Établissement introuvable.');
        }

        $formation = new Formation($slug, $titre, $etablissement);
        $formation->setPrix(250000)
            ->setDescription('Les bases de PHP moderne.')
            ->setObjectifs(['Comprendre les variables et les fonctions']);
        $formation->addModule(new ModuleFormation('Premiers pas', 1));

        $this->em->persist($formation);
        $this->em->flush();

        return $formation;
    }
}
```

`persist()` indique à Doctrine qu’un nouvel objet doit être enregistré. **`flush()` exécute les écritures SQL.** Un objet existant lu par un repository est déjà géré : modifiez-le puis appelez `flush()` pour mettre à jour la ligne. Les transactions et les contraintes uniques restent à gérer au niveau applicatif lorsque vous ajoutez des opérations concurrentes ou des formulaires réels.

## 8. Ajouter d’autres entités ou champs

MakerBundle est installé en dépendance de développement :

```bash
php bin/console make:entity --env=dev
php bin/console make:migration --env=dev
```

Donnez le nom de la nouvelle entité ou de celle à compléter, puis définissez ses propriétés et relations. Examinez le fichier généré dans `migrations/` avant d’exécuter :

```bash
php bin/console doctrine:migrations:migrate --env=dev
php bin/console doctrine:schema:validate --env=dev
```

Une migration ultérieure peut être destructive : sauvegardez vos données et relisez les `DROP`, suppressions de colonnes et changements de type. **Ne faites pas de `doctrine:database:drop`, `doctrine:schema:drop` ou de purge de fixtures pour installer cette version.** Un rollback de la migration initiale effacerait les trois tables métier.

## 9. Vérifier et dépanner

Avec le serveur HTTP en cours d’exécution, dans un deuxième terminal :

```bash
php bin/console doctrine:mapping:info --env=dev
php bin/console doctrine:schema:validate --env=dev
php tests/persistence.php
php tests/check.php
```

Le test de persistance crée ses propres objets dans une transaction puis effectue un **ROLLBACK** : il ne laisse pas de données de test. Il vérifie la création, la relecture SQL, la relation établissement, le JSON, l’ordre des modules, la mise à jour et les modules orphelins. Le script HTTP vérifie les pages, filtres et ressources du jeu de démonstration ; certaines attentes peuvent changer si vous modifiez ce jeu.

| Erreur | Vérification |
| --- | --- |
| `could not find driver` | Installer/activer `pdo_mysql` dans le PHP CLI sélectionné, puis redémarrer le serveur PHP. |
| `Connection refused` | Vérifier que MySQL fonctionne, écoute sur `127.0.0.1:3306`, et que vous utilisez le bon port. |
| `Access denied` | Vérifier le compte, le mot de passe, les droits sur `nuru` et l’hôte associé au compte. |
| `Unknown database` | Créer la base avec le compte administrateur ; la commande de migration crée les tables, pas la base MySQL. |
| `Table ... doesn't exist` | Appliquer la migration sur la base réellement utilisée par `DATABASE_URL`. |
| Namespace Doctrine absent après une mise à jour | Vérifier `composer install`, puis vider le cache de l’environnement `dev`. |
| Catalogue vide | Exécuter l’import sur la bonne base, ou créer vos formations ; un catalogue SQL vide n’utilise pas automatiquement les données PHP. |
| Ancienne base encore utilisée | Vérifier `.env.dev.local`, `.env.local.php` et les variables du terminal, puis redémarrer le serveur. |

## Périmètre de cette livraison

La base persiste le noyau pédagogique du catalogue. La version courante ajoute également les utilisateurs authentifiés, les rôles et l’administration : consultez [le guide utilisateurs et administration](utilisateurs-admin.md). L’inscription publique des comptes et leur validation e-mail/admin sont disponibles dans [le guide inscriptions](inscriptions-validation-email.md). Les inscriptions aux formations, paiements, candidatures et progressions individuelles restent à implémenter.

Les migrations ne sont jamais exécutées automatiquement au démarrage du conteneur. Le `Dockerfile` inclut désormais `pdo_mysql` et le dossier `migrations/`. Pour une publication future, configurez une base accessible depuis l’hébergement : son `127.0.0.1` n’est pas votre poste de développement.

## Référence

[Documentation Symfony 7.4 — Bases de données et Doctrine](https://symfony.com/doc/7.4/doctrine.html).
