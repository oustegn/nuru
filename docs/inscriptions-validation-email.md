# Inscriptions, vérification d’e-mail et validation admin

Cette version complète Symfony Security avec une **inscription publique** pour les apprenants, formateurs et représentants d’établissements. Les comptes sont persistés dans la table **`user`**, indépendante de la marque du site. Le transport choisi est **votre serveur SMTP**, configuré localement ; aucun identifiant n’est inclus dans les archives.

## 1. Parcours d’activation

| Profil | Conditions avant connexion |
| --- | --- |
| Apprenant | Formulaire accepté puis e-mail vérifié |
| Formateur | Formulaire accepté, e-mail vérifié et inscription approuvée par un administrateur |
| Établissement | Compte de son représentant, e-mail vérifié et inscription approuvée par un administrateur |

Tous les nouveaux comptes publics sont **inactifs** à leur création. L’ordre des deux validations n’a pas d’importance : si l’admin approuve avant la vérification d’e-mail, le compte reste inactif ; si l’e-mail est vérifié avant la décision, le compte reste aussi inactif. Les deux conditions sont nécessaires pour les partenaires.

L’apprenant n’exige pas de validation admin. Un compte établissement ne peut pas se connecter à l’administration. Aucun profil public ne permet de créer un administrateur.

Les anciens comptes créés explicitement par la commande locale restent des **comptes internes**, avec leurs accès existants. La migration ne prétend pas que leurs adresses ont été vérifiées. Elle ne supprime ni les comptes ni les formations.

## 2. Mettre à jour votre poste

L’archive différentielle se reporte sur la version précédente **avec la table `user`**, après comparaison de vos personnalisations. Le projet complet contient tout l’historique de migrations, y compris le renommage de la table et cette nouvelle extension.

Arrêtez le serveur de développement, sauvegardez vos fichiers et votre base, reportez les fichiers modifiés et gardez vos `.env`, `.env.dev` et `.env.dev.local`. Les versions de **`composer.json` et `composer.lock` doivent être copiées ensemble**.

```bash
composer install
php bin/console cache:clear --env=dev
php bin/console doctrine:migrations:migrate --env=dev
php bin/console doctrine:schema:validate --env=dev
```

La migration `Version20261009142021` ajoute les états d’inscription, la vérification d’adresse, l’audit de décision, la relation établissement et les coordonnées. Ses valeurs par défaut préservent les accès des comptes existants. N’exécutez pas de purge ou de suppression de schéma.

## 3. Configurer votre SMTP dans `.env.dev.local`

Votre `.env` doit sélectionner `APP_ENV=dev`. Ajoutez dans **`.env.dev.local`**, qui est exclu de Git :

```dotenv
MAILER_DSN="smtp://IDENTIFIANT_ENCODE:MOT_DE_PASSE_ENCODE@smtp.votre-domaine.fr:587?require_tls=true"
MAILER_FROM="no-reply@votre-domaine.fr"
APP_PUBLIC_ORIGIN="http://127.0.0.1:3000"
SESSION_SECURE=0
SESSION_SAMESITE=lax
```

Il s’agit d’un modèle, pas de paramètres fonctionnels : remplacez l’hôte, le port et les identifiants selon votre prestataire. Pour un SMTP à TLS implicite sur le port 465, le modèle est :

```dotenv
MAILER_DSN="smtps://IDENTIFIANT_ENCODE:MOT_DE_PASSE_ENCODE@smtp.votre-domaine.fr:465"
```

Encodez les caractères spéciaux de l’identifiant et du mot de passe comme des composants d’URL, par exemple `@` → `%40`, `:` → `%3A`, `#` → `%23`. Ne désactivez pas la vérification du certificat TLS pour contourner un problème de configuration. Le fournisseur peut exiger un mot de passe d’application ou un identifiant dédié.

`MAILER_FROM` doit être une adresse d’expéditeur autorisée par votre SMTP. Configurez les conditions d’envoi de votre domaine selon votre prestataire, notamment les enregistrements DNS nécessaires. La réussite d’une remise au serveur SMTP ne garantit pas l’arrivée en boîte de réception : vérifiez aussi les indésirables et les retours du fournisseur.

**Ne partagez pas les identifiants SMTP dans la conversation, dans Git ou dans des captures de diagnostic.** Les fichiers d’exemple livrés utilisent `MAILER_DSN=null://null` : l’envoi y est désactivé, aucun compte n’est activé sans vérification. Ce n’est pas un contournement de sécurité.

Après modification des paramètres, videz le cache et redémarrez :

```bash
php bin/console cache:clear --env=dev
php -S 127.0.0.1:3000 -t public router.php
```

## 4. Origine des liens d’e-mail

**`APP_PUBLIC_ORIGIN` doit correspondre à l’adresse utilisée pour ouvrir le site.** Les liens ne sont jamais construits à partir d’un Host arbitraire envoyé au serveur.

Pour votre poste local, `http://127.0.0.1:3000` convient. Un destinataire ouvrant ce lien depuis un autre appareil arriverait sur son propre localhost, pas sur votre poste : pour tester avec des tiers ou déployer, fournissez une vraie origine HTTPS accessible à ces destinataires.

Pour un site HTTPS intégré dans un autre site :

```dotenv
APP_PUBLIC_ORIGIN="https://formation.votre-domaine.fr"
SESSION_SECURE=1
SESSION_SAMESITE=none
```

`SITE_PUBLIC_ORIGIN` reste le paramètre des métadonnées publiques ; `APP_PUBLIC_ORIGIN` est celui des liens de vérification. Les deux peuvent correspondre au même domaine en production. Aucun domaine d’exemple ne doit rester dans votre configuration réelle.

## 5. Formulaires publics

Ouvrez **http://127.0.0.1:3000/inscription** :

| Adresse | Formulaire |
| --- | --- |
| `/inscription/apprenant` | Prénom, nom, e-mail, mot de passe confirmé, téléphone facultatif |
| `/inscription/formateur` | Même identité, spécialité et présentation pédagogiques |
| `/inscription/etablissement` | Identité du représentant, nom, adresse, ville de l’établissement, site facultatif |

Le mot de passe exige au moins 12 caractères. Il est immédiatement haché ; le formulaire n’est pas lié directement à l’entité pour éviter toute modification des rôles ou de l’état actif depuis une requête.

L’inscription d’un établissement crée une nouvelle structure reliée à son représentant. Elle ne donne aucun droit sur un établissement déjà existant. La gestion de plusieurs représentants et l’association contrôlée à une structure existante sont des extensions ultérieures.

Les réponses de renvoi de vérification sont génériques et ne révèlent pas si une adresse est connue, déjà vérifiée ou refusée. Les soumissions et renvois sont limités pour éviter les abus.

## 6. Vérifier l’e-mail

Le message contient un lien valable **24 heures**, basé sur un jeton aléatoire de 256 bits. Seule l’empreinte du jeton est stockée en base.

L’ouverture du lien affiche une page ; elle ne marque pas l’adresse comme vérifiée. Le destinataire doit cliquer sur **« Confirmer mon adresse e-mail »**. Cette confirmation POST protégée par CSRF évite l’activation par un robot qui ouvre automatiquement les liens des e-mails.

Après confirmation, le jeton est consommé. Un nouveau renvoi remplace l’ancien lien ; un lien expiré, remplacé, déjà utilisé ou associé à une demande refusée ne permet pas l’activation.

Le formulaire **`/verification-email/renvoyer`** demande seulement l’adresse. Le délai minimum entre deux envois est de deux minutes. Il y a également des limites par adresse et IP. En cas d’envoi indisponible, le compte demeure inactif ; configurez ou corrigez le SMTP, puis réessayez le renvoi après le délai.

## 7. Valider dans l’interface admin

Connectez-vous avec un compte administrateur existant, puis ouvrez **`/admin/inscriptions`**, accessible dans la navigation latérale. Le tableau de bord affiche le nombre de demandes en attente.

La liste est paginée par vingt demandes et filtrable : en attente, approuvées, refusées ou toutes. Chaque dossier présente identité, profil, coordonnées, état de l’e-mail et informations de la structure ou de l’expertise pédagogique.

Dans la fiche, l’admin peut **approuver** ou **refuser avec un motif obligatoire**. Les décisions sont réservées à `ROLE_ADMIN`, effectuées par POST CSRF et enregistrent le décideur ainsi que la date. Un refus ne supprime pas le dossier et laisse le compte inactif. Une demande déjà traitée ne peut pas recevoir une seconde décision dans cette version.

**L’approbation ne contourne jamais la vérification e-mail.** Le verrouillage SQL empêche deux décisions simultanées incompatibles. L’admin ne peut pas statuer sur sa propre inscription. Les mots de passe, leurs hashes et les liens de vérification ne figurent pas dans les dossiers admin.

L’interface conserve l’audit de la décision ; cette livraison n’ajoute pas de notification e-mail de décision ni de réouverture de dossier refusé. L’envoi implémenté est celui de vérification d’adresse.

## 8. Espaces après activation

L’apprenant utilise `/espace/apprenant`, le formateur `/espace/formateur`, et le représentant `/espace/etablissement`. Ce dernier voit uniquement la structure liée à son compte. Les accès refusés ne peuvent pas être contournés en modifiant un champ du formulaire.

Les inscriptions aux cours, suivis pédagogiques, paiements, admissions et formulaires de gestion de programme restent des étapes distinctes : cette livraison porte sur **l’inscription des comptes et leur validation**.

## 9. Vérifications réalisées

**80 contrôles d’inscription**, ainsi que les 90 contrôles de sécurité des comptes existants, 60 contrôles du site et 9 contrôles transactionnels de persistance, ont été exécutés avec succès. Une panne SMTP a aussi été simulée : le compte reste inactif et la page signale l’envoi indisponible.

Les parcours ont été testés dans une base MySQL de validation séparée avec une **boîte SMTP de capture localhost**, sans e-mail transmis à l’extérieur. Les tests couvrent les trois profils, les deux ordres des validations admin/e-mail, le refus, le CSRF, l’activation, l’expiration et le remplacement des liens. Les comptes de test sont supprimés après les contrôles, sans modification des comptes existants.

Le serveur SMTP personnel du développeur n’a pas été testé : ses paramètres sont à renseigner sur son poste. Les outils de capture dans `tests/` servent exclusivement aux tests locaux et ne sont pas des endpoints publics de l’application.

## 10. Dépannage

| Symptôme | Contrôle |
| --- | --- |
| Aucun e-mail reçu | Vérifier MAILER_DSN, MAILER_FROM, la disponibilité SMTP, TLS, l’origine de l’application et les indésirables. |
| Service non configuré | Remplacer `null://null` dans `.env.dev.local`, puis vider le cache et redémarrer. |
| Formateur/établissement ne peut pas se connecter | Vérifier séparément l’e-mail et la décision admin. |
| E-mail vérifié mais compte en attente | L’admin doit examiner le dossier. |
| Admin a approuvé mais accès impossible | La vérification d’e-mail est encore nécessaire. |
| Lien expiré | Utiliser le renvoi ; le lien précédent sera invalidé. |
| Refus sans changement | Un motif non vide est obligatoire ; une demande traitée ne peut pas être retraitée. |
| Réponse 403 dans l’admin | Le compte doit être un administrateur actif. |

Avant une production multi-instance, fournissez un stockage partagé des sessions et limites, une base durable, des sauvegardes, un SMTP fiable et la configuration opérationnelle appropriée. Les migrations et décisions ne sont pas exécutées automatiquement au démarrage du conteneur.
