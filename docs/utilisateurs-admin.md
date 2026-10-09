# Nuru — Utilisateurs, connexion locale et tableau de bord admin

Cette version ajoute une entité **User** persistée en MySQL, une connexion par e-mail et mot de passe avec **Symfony Security**, ainsi que des espaces protégés pour les apprenants, formateurs et administrateurs. Elle complète la version MySQL : les formations et modules existants ne sont ni supprimés ni réinitialisés.

## 1. Mettre à jour votre installation

L’archive `nuru-symfony-utilisateurs-admin.zip` contient le projet complet, les nouvelles dépendances verrouillées et les migrations de création et de renommage. Extrayez-la dans un nouveau dossier puis reportez votre `.env`, votre `.env.dev` et vos personnalisations. Si vous travaillez déjà avec Git, comparez les fichiers plutôt que d’écraser vos propres changements.

**Utilisez ensemble le nouveau `composer.json` et le nouveau `composer.lock`.** Le dossier `vendor/` n’est jamais inclus dans l’archive : depuis la racine du nouveau projet, exécutez :

```bash
composer install
composer dump-autoload
php bin/console cache:clear --env=dev
php bin/console doctrine:migrations:migrate --env=dev
php bin/console doctrine:schema:validate --env=dev
```

Vérifiez la base cible avant de confirmer la migration. Si vous aviez déjà appliqué la première migration MySQL, Doctrine exécute les migrations encore en attente : `Version20261009100716` crée les comptes et leur affectation, puis `Version20261009124300` renomme la table historique `nuru_user` en `user`. Sur une base neuve, tout l’historique est exécuté. Le nom final est `user`, avec la colonne nullable `formateur_utilisateur_id` dans `formation` et sa clé étrangère. Consultez [le guide de renommage](renommage-table-user.md) si la version comptes est déjà installée.

Ne supprimez aucune table et ne lancez pas de purge pour effectuer cette mise à jour. Le rollback de la nouvelle migration effacerait les comptes et leurs affectations : ce n’est pas une procédure d’installation.

Pour une toute première installation MySQL, consultez d’abord [le guide MySQL local](mysql-local.md). L’import `app:database:seed` reste additif et **ne crée aucun utilisateur**.

## 2. Configurer localhost HTTP dans `.env.dev`

Votre `.env` doit sélectionner :

```dotenv
APP_ENV=dev
```

Dans `.env.dev`, conservez votre vrai secret Symfony et votre connexion MySQL. Ajoutez les paramètres de session :

```dotenv
APP_DEBUG=0
APP_SECRET=VOTRE_SECRET_SYMFONY_EXISTANT
CATALOG_SOURCE=database
SITE_PUBLIC_ORIGIN=
DATABASE_URL="mysql://nuru_app:VOTRE_MOT_DE_PASSE_MYSQL@127.0.0.1:3306/nuru?serverVersion=8.0.46&charset=utf8mb4"
SESSION_SECURE=0
SESSION_SAMESITE=lax
```

Adaptez la version MySQL à la vôtre. Ces identifiants sont ceux de **la base**, pas ceux de l’utilisateur qui se connectera au site. `.env` et `.env.dev` sont exclus de Git ; vous pouvez aussi utiliser `.env.dev.local`, qui prend priorité. Les fichiers d’exemple partageables ne contiennent aucun identifiant réel.

Pour un aperçu HTTPS intégré dans un autre site, choisissez explicitement :

```dotenv
SESSION_SECURE=1
SESSION_SAMESITE=none
```

Un cookie `Secure` ne doit pas être utilisé pour votre installation locale en HTTP : le navigateur pourrait ne pas le renvoyer. Après changement de configuration, videz le cache de l’environnement concerné puis redémarrez le serveur. Vérifiez aussi les éventuelles valeurs présentes dans `.env.dev.local`, dans un ancien `.env.local.php` ou dans votre terminal.

## 3. Créer votre premier administrateur

Aucun mot de passe universel, compte `admin/admin` ou administrateur par défaut n’est fourni. Vous créez explicitement votre compte sur votre poste :

```bash
php bin/console app:user:create admin@nuru.local VotrePrenom VotreNom --role=ROLE_ADMIN --env=dev
```

Remplacez l’adresse et le nom par les vôtres. Le terminal demande le mot de passe et sa confirmation **en saisie masquée**. Choisissez au moins **12 caractères**. Le mot de passe n’est pas fourni dans la ligne de commande ni stocké en clair dans MySQL ; il est haché par Symfony.

La commande refuse de modifier un compte déjà associé à cette adresse e-mail. Elle refuse aussi le mode `--no-interaction` : aucun compte ne doit être créé avec un mot de passe vide ou un secret automatique. Si votre terminal ne permet pas de masquer la saisie, la création est refusée au lieu d’afficher votre mot de passe.

Cette étape se réalise dans un terminal local autorisé. Ne transmettez pas vos mots de passe dans une conversation ou dans le dépôt Git.

## 4. Créer un apprenant ou un formateur

Sans option de rôle, le compte est apprenant :

```bash
php bin/console app:user:create apprenant@nuru.local Fatou Barry --env=dev
```

Pour un formateur uniquement :

```bash
php bin/console app:user:create formateur@nuru.local Mamadou Diallo --role=ROLE_FORMATEUR --env=dev
```

Pour un même utilisateur apprenant **et** formateur :

```bash
php bin/console app:user:create double-role@nuru.local Aissatou Camara --role=ROLE_APPRENANT --role=ROLE_FORMATEUR --env=dev
```

Les options `--role` sont répétables. Un rôle fourni explicitement remplace le rôle apprenant par défaut ; pour cumuler, indiquez les deux options. Tous les comptes obtiennent également `ROLE_USER`. Les rôles applicatifs acceptés sont les suivants :

| Rôle | Autorisations |
| --- | --- |
| `ROLE_APPRENANT` | Profil et espace apprenant |
| `ROLE_FORMATEUR` | Espace formateur et lecture de ses formations affectées |
| `ROLE_ETABLISSEMENT` | Espace du représentant lié à sa structure ; inscription publique soumise à validation admin/e-mail |
| `ROLE_ADMIN` | Tableau de bord et listes administratives ; accès aux deux espaces pédagogiques par hiérarchie |

Un utilisateur apprenant ou formateur n’est **jamais** administrateur par défaut. Les rôles ne sont ni choisis sur le formulaire de connexion ni acceptés depuis une requête publique. L’interface d’administration actuelle n’expose aucune modification de rôle.

## 5. Démarrer et se connecter

```bash
php -S 127.0.0.1:3000 -t public router.php
```

Ouvrez **http://127.0.0.1:3000/connexion** et utilisez l’e-mail et le mot de passe du compte applicatif que vous venez de créer.

Après connexion, `/mon-espace` dirige vers l’administration si vous êtes admin, sinon vers l’espace formateur si vous avez ce rôle, puis vers l’espace apprenant. Un compte à double rôle peut choisir l’autre espace dans la navigation latérale.

| Adresse | Accès |
| --- | --- |
| `/connexion` | Formulaire public, protégé par CSRF |
| `/mon-espace` | Compte connecté ; redirection selon le rôle |
| `/espace/apprenant` | Apprenant ou administrateur |
| `/espace/formateur` | Formateur ou administrateur |
| `/admin` | Administrateur uniquement |
| `/admin/utilisateurs` | Liste des comptes, recherche nom/prénom/e-mail et pagination de 20 lignes |
| `/admin/formations` | Liste des formations et affectations formateurs, paginée |
| `/admin/etablissements` | Liste des établissements persistés, paginée |
| `/deconnexion` | POST avec token CSRF ; pas de lien GET de déconnexion |

Sans session, un espace privé redirige vers la connexion. Avec un compte sans le rôle requis, l’application répond **403**. Les pages privées sont `noindex` et `no-store` ; les mots de passe et leurs hashes ne figurent jamais dans les listes.

Le tableau de bord affiche les volumes réels de MySQL : utilisateurs, formations, établissements et rôles. Les compteurs de rôles portent sur les rôles stockés explicitement et ne sont pas exclusifs : un compte à double rôle compte dans les deux catégories. Les listes restent en lecture seule ; l’extension d’inscription ajoute une interface séparée d’approbation/refus des formateurs et établissements.

## 6. Affecter une formation à un formateur

La version précédente stockait un libellé de formateur sous forme de texte. Il est conservé pour ne pas altérer les données du catalogue ; il ne crée pas automatiquement un compte.

Pour relier une formation existante à un utilisateur formateur actif :

```bash
php bin/console app:formation:assign developpement-web-symfony formateur@nuru.local --env=dev
```

Le compte doit avoir `ROLE_FORMATEUR` ou `ROLE_ADMIN`. Un compte apprenant seul est refusé, sans changement automatique de ses rôles. Une fois l’affectation faite, le formateur voit la formation dans son espace et le catalogue affiche son nom. Le tableau de bord admin montre le compte affecté.

Une relance avec un autre formateur remplace cette affectation : faites-le seulement si c’est votre intention. Les autres données de la formation, ses modules et son établissement sont conservés.

## 7. Structure de l’entité User

| Champ | Type / règle |
| --- | --- |
| `id` | Entier généré par MySQL |
| `email` | Chaîne de 180 caractères maximum, normalisée en minuscules, unique |
| `prenom`, `nom` | Chaînes non vides de 100 caractères maximum |
| `password` | Hash reconnu par PHP, jamais le mot de passe en clair |
| `roles` | JSON, liste limitée aux rôles applicatifs autorisés |
| `actif` | Booléen, actif à la création ; un compte inactif ne peut pas se connecter ; une session ouverte est invalidée dès la requête suivante après suspension |
| `cree_le` | Date de création immutable |
| `formationsEnseignees` | Collection inverse des formations affectées |

`User` implémente `UserInterface` et `PasswordAuthenticatedUserInterface`. Le provider charge le compte depuis MySQL par e-mail. La relation `Formation → User` est optionnelle, sans suppression en cascade des formations lorsque le compte est supprimé. La sérialisation de session omet les associations Doctrine.

## 8. Vérifications

Les commandes sans écriture métier sont :

```bash
php bin/console lint:container --env=dev
php bin/console doctrine:mapping:info --env=dev
php bin/console doctrine:schema:validate --env=dev
```

Les tests existants restent disponibles :

```bash
php tests/persistence.php
php tests/check.php
```

Le script `tests/authentication.py` teste la connexion, les rôles simples et cumulés, les refus admin, le CSRF et la déconnexion. Il crée ses **propres comptes temporaires**, puis les nettoie. Par protection, il refuse une base dont le nom ne finit pas par `_test` ou `_validation` : ne l’exécutez pas sur votre base de travail ou de production. Son helper ne touche pas les comptes existants. Il nécessite Python et le module `requests`.

Dans cette livraison, **90 contrôles HTTP de sécurité**, les 60 contrôles du site et les 9 contrôles transactionnels MySQL ont été exécutés avec succès dans une base de validation séparée. La création CLI avec mot de passe masqué et rôle admin explicite a aussi été vérifiée. Aucun compte de test ni administrateur par défaut n’est conservé dans l’archive.

## 9. Dépannage

| Symptôme | Correction |
| --- | --- |
| `SecurityBundle` ou `DoctrineBundle` introuvable | Vérifier que `composer.json` et `composer.lock` proviennent de la même nouvelle version, puis exécuter `composer install`. |
| Table `user` absente | Appliquer la nouvelle migration sur la bonne base. |
| Retour à la connexion en boucle en localhost HTTP | Utiliser `SESSION_SECURE=0` et `SESSION_SAMESITE=lax`, vérifier les surcharges, redémarrer et réessayer. |
| Connexion impossible avec un compte existant | Vérifier le mot de passe et l’état actif ; après plusieurs échecs, attendre la fin de la limitation des tentatives. |
| Réponse 403 sur `/admin` | Le compte connecté doit posséder le rôle administrateur ; apprenant et formateur ne suffisent pas. |
| Espace formateur vide | Affecter une formation à ce compte avec `app:formation:assign`. |
| Espace apprenant sans cours suivis | Les inscriptions et progressions individuelles ne sont pas encore implémentées : aucune activité fictive n’est attribuée à un vrai compte. |

## Limites explicites

L’inscription publique et la confirmation d’e-mail sont désormais disponibles : consultez [le guide des inscriptions](inscriptions-validation-email.md). Il reste à ajouter la réinitialisation du mot de passe par e-mail, les formulaires d’édition de comptes/rôles, les inscriptions aux cours, la progression individuelle, les paiements ou les admissions. Les anciens espaces `/demo/*` restent publics et clairement illustratifs ; ils ne sont pas les espaces authentifiés.

L’application utilise une session serveur locale, adaptée à localhost et à une instance de développement. Avant une production multi-instance, prévoyez un stockage de session partagé, une base hébergée accessible, des sauvegardes et les règles opérationnelles appropriées. Le conteneur ne lance pas de migration ou de création d’administrateur automatiquement.
