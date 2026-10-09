# Suivi du template Nuru

- [x] Application véritablement Symfony 7.4 LTS et Twig, PHP 8.2 minimum, dépendances verrouillées, structure modulaire et README de personnalisation.
- [x] Accueil moderne, responsive et français ; recherche, catégories professionnelles/BTS/Licence/Master, apprentissage en ligne et présentiel, partenaires, modèle 85 % / 15 %, aucun faux témoignage ni accréditation inventée.
- [x] Catalogue avec filtres domaine/programme/modalité, recherche GET, état vide et réinitialisation ; fiches avec modules, objectifs, durée, prix GNF, informations présentiel et vraie 404.
- [x] Page établissements : partenaires fictifs identifiés, candidatures et gestion pédagogique ; validation et diplôme sous contrôle de l’établissement.
- [x] Démonstrations apprenant, formateur, établissement ; navigation entre rôles, progrès, ressources, certificats non officiels, revenus et admissions fictifs ; aucun compte sécurisé, paiement ou inscription réelle.
- [x] Design éditorial ivoire/bleu nuit/émeraude, Fraunces/Plus Jakarta Sans, visuel original, identité remplaçable, menu accessible, focus et réduction des animations.
- [x] Aperçu Symfony port 3000, routes manifestées, conteneur de production reproductible avec santé HTTP, sauvegarde Git, archive ZIP complète et documentation.

## Persistance MySQL locale

- [x] Doctrine ORM, migrations et pilote PDO MySQL configurés ; exemples `.env.dev` compatibles avec la configuration locale choisie et secrets exclus de Git.
- [x] Entités Etablissement, Formation et ModuleFormation, relations, clés étrangères et contraintes d’unicité ; prix GNF entiers et champs du catalogue conservés.
- [x] Catalogue lu depuis MySQL lorsque CATALOG_SOURCE=database ; mode démo explicite conservé, sans repli silencieux sur une panne de base.
- [x] Migration initiale MySQL, initialisation additive qui ne remplace pas les données existantes et création persistante d’une formation via commande ; démonstration de persist/flush et lecture après redémarrage.
- [x] Validation MySQL, guide de création de la base et de l’utilisateur local, code sauvegardé et nouvelle archive complète sans secrets.

## Utilisateurs et tableau de bord administrateur

- [x] Entité User persistante, e-mail unique normalisé, nom/prénom, mot de passe haché, rôles apprenant/formateur/admin cumulables ; migration additive préservant les formations.
- [x] Connexion locale Symfony Security avec CSRF, limitation des tentatives, déconnexion POST CSRF et cookies adaptés au localhost HTTP ou à l’aperçu HTTPS ; aucune inscription admin publique ni compte par défaut.
- [x] Espaces apprenant et formateur authentifiés, profil réel et formations affectées via une relation nullable Formation vers User ; anciens espaces de démo distincts et publics.
- [x] Tableau de bord /admin réservé à ROLE_ADMIN, statistiques MySQL et listes paginées utilisateurs/formations/établissements en lecture seule ; apprenant et formateur non admins refusés.
- [x] Commandes de création de compte avec mot de passe masqué et d’affectation d’une formation ; tests des rôles, accès et CSRF ; guide local et nouvelle archive complète sans secrets.

## Table User indépendante du nom du site

- [x] L’entité User utilise la table SQL `user` avec quoting explicite ; une migration de renommage conserve les comptes, les hashes et les affectations ; requêtes natives et guides actualisés, archive différentielle livrée.

## Inscription publique et vérification e-mail

- [x] Formulaires d’inscription apprenant, formateur et établissement ; rôle public limité au profil choisi ; mots de passe confirmés et hachés, validation serveur, CSRF et doublons gérés sans divulgation de compte.
- [x] Comptes publics inactifs avant vérification e-mail ; apprenants activés après vérification ; formateurs et établissements activés uniquement après e-mail vérifié et approbation admin, quel que soit l’ordre. Comptes existants préservés.
- [x] E-mail de vérification à jeton aléatoire, empreinte en base, usage unique et expiration ; renvoi limité ; transport SMTP ou boîte locale explicitement configuré et erreur d’envoi visible sans activation.
- [x] Validation ou refus des formateurs/établissements dans l’interface admin, protégés ROLE_ADMIN et POST CSRF, demandes paginées, audit des décisions et motif de refus ; aucune validation publique.
- [x] Compte représentant lié à son établissement, espace institutionnel protégé ; navigation, migrations, documentation locale et archives mises à jour ; tests des parcours de validation et accès.
