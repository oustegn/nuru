# Table de l’entité User : nom indépendant de la marque

La table de l’entité `App\Entity\User` s’appelle désormais **`user`**, au lieu de `nuru_user`. Changer le nom commercial du site ne nécessite donc aucun renommage de cette table. L’entité PHP, les rôles et les comptes ne changent pas.

## Fichiers techniques

| Fichier | Changement |
| --- | --- |
| `src/Entity/User.php` | Nom de table user, avec quoting Doctrine explicite |
| `src/Repository/UserRepository.php` | Statistiques SQL lues dans `user`, identifiant protégé par backticks |
| `migrations/Version20261009124300.php` | Renommage de la table et de son index unique, sans recréer les comptes |

Les autres fichiers de l’archive mettent à jour les guides et le suivi du projet. Elle s’applique à la dernière version **utilisateurs + admin**, pas à l’ancienne version sans comptes.

## Installation déjà migrée

Sauvegardez votre base et comparez les fichiers de l’archive avec vos personnalisations avant de les reporter. Gardez votre `.env.dev` et vos identifiants MySQL. Arrêtez momentanément le serveur de développement pendant la mise à jour pour éviter qu’une requête utilise le nouveau code avec l’ancien nom de table.

Depuis la racine du projet :

```bash
php bin/console cache:clear --env=dev
php bin/console doctrine:migrations:migrate --env=dev
php bin/console doctrine:schema:validate --env=dev
```

Redémarrez ensuite votre serveur local :

```bash
php -S 127.0.0.1:3000 -t public router.php
```

La migration exécute le renommage MySQL :

```sql
RENAME TABLE `nuru_user` TO `user`;
ALTER TABLE `user` RENAME INDEX `UNIQ_A094DD53E7927C74` TO `UNIQ_8D93D649E7927C74`;
```

**N’exécutez pas ces requêtes manuellement en plus de la migration.** Aucun compte n’est supprimé, aucun mot de passe n’est réinitialisé, aucune autorisation n’est changée. Les identifiants et affectations `formation.formateur_utilisateur_id` sont conservés ; MySQL met à jour la référence de clé étrangère lors du renommage. L’index conserve l’unicité de l’e-mail.

Il n’y a pas de nouvelle dépendance Composer pour ce seul ajustement. N’utilisez pas `doctrine:schema:update --force` ou une purge pour renommer la table : la migration fournie conserve les données et l’historique.

## Base neuve

Gardez les migrations précédemment livrées. Sur une installation neuve, Doctrine applique l’historique : schéma pédagogique, création des comptes sous le nom historique `nuru_user`, puis renommage vers `user`. Le nom final est bien **`user`**.

La migration `Version20261009100716` n’est volontairement pas modifiée : certains postes l’ont déjà exécutée. Changer seulement son SQL ne renommerait pas la table sur ces installations.

## Si la table a déjà été renommée à la main

La nouvelle migration s’arrête si la source `nuru_user` n’existe pas ou si une table `user` existe déjà. Elle n’écrase ni ne fusionne des tables. Ne supprimez pas une table pour contourner ce contrôle ; vérifiez d’abord les données, la clé étrangère, l’index et l’historique Doctrine. L’archive suppose le schéma de la version utilisateurs/admin précédente, sans renommage manuel.

Si la table s’appelle réellement `nuru_use` sur votre poste, et non `nuru_user`, il s’agit d’un écart avec les fichiers livrés : adaptez le nom source après vérification de votre schéma, plutôt que d’exécuter aveuglément cette migration.

## Contrôles

Le mapping Doctrine et les statistiques administratives ont été adaptés. Des comptes créés **avant** le renommage dans une base de validation ont pu se connecter **après** le renommage ; leur affectation formateur a été conservée. Aucun accès à votre poste local ou à votre base locale n’a été effectué.

Le schéma final est validé par Doctrine. Le renommage complet et son inverse ont également été exécutés sur une base jetable, avec identité des données avant/après. Les 90 contrôles HTTP de sécurité passent après le renommage.
