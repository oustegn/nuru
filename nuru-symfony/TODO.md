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
