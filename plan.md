# Plan — Template Symfony de la plateforme de formation

## 1. Objectif et périmètre

Créer un **template de site moderne, responsive et entièrement en français**, adapté au projet de formation en Guinée et en Afrique francophone décrit dans votre présentation PDF.

Le livrable sera une véritable application **Symfony avec templates Twig**, et non une maquette React présentée comme du Symfony. Il comprendra des pages publiques et des espaces de démonstration pour les apprenants, formateurs et établissements.

**Périmètre : un template navigable avec données fictives cohérentes.** Les paiements, comptes utilisateurs, admissions, examens et certifications réels ne seront pas activés. Le document présente la vision complète du produit ; cette première réalisation en donnera une base visuelle et technique réutilisable, sans prétendre livrer tout le système de gestion.

## 2. Pages et comportements prévus

### Accueil — `/`
- Navigation : formations, programmes académiques, établissements et accès à la démonstration.
- Bandeau principal : « Votre avenir s’apprend ici », avec recherche et appel à explorer les formations.
- Mise en avant des formations professionnelles, BTS, Licence et Master.
- Sélection de formations, présentation de l’apprentissage en ligne et du présentiel, bénéfices pour les partenaires et pied de page complet.
- Présentation fidèle du modèle économique : **85 % pour les partenaires et 15 % pour la plateforme**.
- Aucun faux témoignage attribué à une personne réelle ni affirmation d’accréditation non établie.

### Catalogue — `/formations`
- Recherche par intitulé et filtres par domaine, type de programme et modalité.
- Résultats issus d’un jeu de données de démonstration, état sans résultat et réinitialisation des filtres.
- Cartes indiquant intitulé, catégorie, durée, modalité et prix d’exemple en **GNF**.
- Liens de l’accueil vers le catalogue préfiltré pour les BTS, Licence et Master.

### Détail d’une formation — `/formations/{slug}`
- Description, objectifs, programme en modules, formateur ou établissement fictif, durée et tarif.
- Pour le présentiel : lieu, dates, places d’exemple et statut « à venir », « en cours » ou « terminé ».
- Bouton d’inscription ouvrant une explication du parcours prévu, explicitement signalé comme démonstration : aucun prélèvement ni inscription effective.
- Réponse 404 pour une formation inexistante.

### Établissements — `/etablissements`
- Présentation de l’espace institutionnel et profils de partenaires fictifs clairement identifiés.
- Mise en avant des programmes, candidatures, gestion pédagogique et modalités d’examen.
- Précision que la validation académique et la délivrance des diplômes restent sous le contrôle de l’établissement partenaire.

### Espaces de démonstration
- `/demo/apprenant` : formations suivies, progression, prochaine session, ressources et exemples de certificats non officiels.
- `/demo/formateur` : formations, apprenants, revenus d’exemple et répartition 85/15 ; aperçu des modules, documents et quiz.
- `/demo/etablissement` : programmes, candidatures, admissions et indicateurs pédagogiques de démonstration.
- Navigation entre rôles visible ; **aucune simulation trompeuse de connexion sécurisée**.
- Les commandes uniquement illustratives seront identifiées comme telles ; les commandes de navigation et de filtrage seront fonctionnelles.

## 3. Direction artistique

- **Mouvement esthétique :** design éditorial contemporain associé à une interface EdTech sobre et haut de gamme.
- **Principes :** lisibilité, respiration, inclusion et hiérarchie claire des actions.
- **Palette :** ivoire pour la chaleur, bleu nuit pour la confiance, vert profond pour la progression, touches d’abricot pour les repères secondaires. Couleur signature : **vert émeraude `#087F68`**.
- **Composition :** accueil asymétrique, texte ample à gauche et photographie à droite ; alternance de sections éditoriales, listes et cartes. Tableaux de bord avec navigation latérale et contenu organisé par priorité.
- **Éléments distinctifs :** motif d’horizon, badges de modalité et fines lignes de progression émeraude.
- **Typographie :** Fraunces pour les grands titres éditoriaux et Plus Jakarta Sans pour les textes et interfaces, avec polices de repli.
- **Marque provisoire :** « Nuru — Apprendre, progresser, transmettre ». Un nom de travail, remplaçable sans modifier la structure du site. Personnalité : accessible, ambitieuse, fiable.
- **Voix :** directe et encourageante. Exemples : « Des compétences concrètes. Des perspectives nouvelles. » et « Trouvez la formation qui vous fait avancer. »
- **Logo :** monogramme N associé à un livre ouvert ; déclinaisons pour l’en-tête et le favicon.
- **Visuels :** une image originale pour l’accueil illustrant des adultes africains en situation d’apprentissage, sans prétendre représenter un partenaire réel. Les tableaux de bord resteront sans décor superflu.
- **Interactions :** actions principales contrastées, états de survol sobres, focus clavier visible et menu mobile accessible.
- **Animation :** transitions courtes de 150 à 250 ms, sans effets lourds ; respect de `prefers-reduced-motion`.

## 4. Mise en œuvre technique

### Architecture
- **Symfony 7.4 LTS**, PHP 8.2 minimum et Composer, avec versions verrouillées par `composer.lock`.
- Rendu serveur **Twig** pour toutes les pages ; CSS responsive et JavaScript léger pour le menu, les onglets et les fenêtres de démonstration.
- Un service de données d’exemple alimente les pages publiques et les tableaux de bord ; aucune base de données nécessaire pour ce template.
- Contrôleurs Symfony et routes explicites ; paramètres de recherche via formulaires GET.
- Métadonnées propres à chaque page publique et HTML sémantique. Les espaces de démonstration seront marqués `noindex`.

### Structure prévue dans `/home/ubuntu/nuruforma`
```text
src/
  Controller/           # Accueil, catalogue, établissements et démonstrations
  Service/DemoCatalog.php # Données fictives et filtres du catalogue
templates/
  base.html.twig        # Structure commune, navigation et pied de page
  components/           # Cartes, badges et composants partagés
  home/                 # Accueil
  formation/            # Catalogue et détail
  establishment/        # Présentation des établissements
  demo/                 # Les trois espaces métier
public/
  assets/css/           # Design system et styles responsive
  assets/js/            # Interactions légères
  assets/images/        # Visuels et identité
  manus-routes.json     # Liste des pages pour l’aperçu
config/                 # Configuration Symfony et Twig
Dockerfile              # Exécution PHP reproductible pour l’hébergement
README.md               # Installation, lancement et personnalisation
TODO.md                 # Suivi des éléments convenus
```

### Environnement et livraison
- L’espace de projet est disponible, mais **PHP et Composer ne sont pas encore installés** dans le sandbox. Leur installation sera effectuée après approbation.
- L’aperçu servira l’application Symfony sur le port configuré **3000** ; le rendu des pages ne dépendra pas d’un serveur React.
- Préparer un conteneur avec un serveur PHP adapté à l’hébergement, installation Composer reproductible et endpoint de santé non authentifié.
- Livrer un **aperçu navigable**, le code sauvegardé du projet et une **archive ZIP du template**, avec instructions d’installation et de personnalisation.
- Ne pas effectuer de publication publique explicite sans autorisation ; tenir compte d’un éventuel réglage de publication automatique déjà autorisé.

## 5. Vérification prévue

- Contrôler la syntaxe PHP, les templates Twig, la configuration Symfony et l’enregistrement des routes.
- Vérifier les réponses HTTP des pages, la 404, le manifeste de routes et le chargement des ressources.
- Vérifier les filtres du catalogue, les liens vers les détails et la cohérence des données partagées.
- Contrôler les règles responsive, la navigation clavier, les libellés et les états des commandes de démonstration ; examiner visuellement uniquement en cas de défaut concret à diagnostiquer.
- Vérifier que les espaces fictifs ne sont pas présentés comme sécurisés, que les certificats ne sont pas officiels et qu’aucune action financière réelle n’est déclenchée.
- Vérifier la présence du code et du README dans l’archive finale.

## 6. Hypothèses et risques

- « Template » est interprété comme une base de site fonctionnelle à personnaliser, **pas comme une plateforme LMS complète en production**.
- Le nom, le logo, les formations, prix, profils et statistiques seront provisoires. Leur remplacement par des contenus réels sera documenté.
- Les images seront optimisées et les scripts limités pour les connexions modestes ; le téléchargement hors ligne et l’application Android/iOS sont hors périmètre.
- L’authentification, la persistance des dossiers, les paiements mobiles, les commissions automatiques, la vidéo protégée et la conformité réglementaire devront faire l’objet d’une phase ultérieure.
- En cas de blocage technique de l’environnement PHP, le problème sera communiqué ; **la technologie demandée ne sera pas remplacée silencieusement**.

**L’implémentation du template commencera après votre approbation de ce plan.**

## Extension demandée le 9 octobre 2026 — MySQL et Doctrine

L’utilisateur souhaite une base MySQL locale et la persistance des entités, avec `.env.dev`. Cette étape ajoute Doctrine ORM, DoctrineBundle, MigrationsBundle et MakerBundle en développement. Noyau initial : `Etablissement` (nom, slug), `Formation` (toutes les données du catalogue, relation établissement) et `ModuleFormation` (titre, position et relation formation). Les objectifs restent un tableau JSON ; les informations de session actuelles restent des champs de formation. Les profils, paiements et admissions ne deviennent pas des processus réels.

`src/Entity/` porte les mappings par attributs ; `src/Repository/` les lectures SQL ; `src/Service/Catalog.php` adapte les entités au contrat Twig existant ; `src/Command/` fournit une initialisation additive et une commande de création démontrant `persist`/`flush`. `migrations/` contient une migration MySQL déterministe. Le catalogue est lu en MySQL avec `CATALOG_SOURCE=database`, sans repli silencieux si la connexion échoue. `CATALOG_SOURCE=demo` conserve l’aperçu sans base. `.env.dev.example` fournit les valeurs publiques, les identifiants réels vont dans `.env.dev.local` exclu de Git. La procédure crée une base dédiée `nuru`, un utilisateur limité à cette base, exécute les migrations puis importe les données fictives sans écraser les modifications. Le conteneur ajoute PDO MySQL et inclut les migrations ; aucune migration n’est déclenchée automatiquement au démarrage. Livrer une nouvelle archive et un guide d’installation. Les tests MySQL sont exécutés dans le sandbox, pas sur le poste utilisateur.

## Extension du 9 octobre 2026 — Utilisateurs et administration

Choix explicite : connexion locale e-mail/mot de passe avec Symfony Security. Une entité `User` (table `nuru_user`) porte e-mail normalisé unique, nom, prénom, hash du mot de passe, rôles JSON cumulables (`ROLE_APPRENANT`, `ROLE_FORMATEUR`, `ROLE_ADMIN`), date de création. `ROLE_USER` est accordé à tout compte ; l’administrateur peut accéder aux espaces pédagogiques via la hiérarchie. Pas d’inscription publique ni de rôle admin fourni par défaut : la commande `app:user:create` demande le mot de passe de façon masquée, le hache et refuse de modifier un compte existant. Les premiers comptes sont créés explicitement par le développeur.

`/connexion` fournit la connexion CSRF, `/deconnexion` utilise un POST CSRF, `/mon-espace` redirige vers le bon espace ; `/espace/apprenant` et `/espace/formateur` sont protégés selon les rôles. Les nouveaux espaces n’inventent pas de progression ou de revenus : le profil est réel, les formations enseignées sont lues en MySQL, les inscriptions restent une phase ultérieure. `/admin` contient les compteurs SQL et listes récentes ; `/admin/utilisateurs`, `/admin/formations`, `/admin/etablissements` donnent des listes en lecture seule paginées et réservées aux administrateurs. Les anciens `/demo/*` restent publics, clairement fictifs et distincts.

`Formation.formateurUtilisateur` ajoute une relation nullable vers User, sans modifier le libellé historique `formateur` ou convertir les profils fictifs en vrais comptes. Une migration additive crée les utilisateurs et la FK ; la commande `app:formation:assign` permet d’affecter un formateur existant. `src/Security/` contient les contrôles d’utilisateur et la redirection après connexion ; `templates/security/`, `templates/account/`, `templates/admin/` réutilisent le design émeraude et les layouts avec sidebar ; `docs/utilisateurs-admin.md` documente la mise à jour locale, les comptes et les limites.

Cookies : mode local HTTP (`SESSION_SECURE=0`, `SESSION_SAMESITE=lax`) et mode aperçu HTTPS (`SESSION_SECURE=1`, `SESSION_SAMESITE=none`) explicitement configurés, sans déduction depuis l’environnement dev/prod ou le schéma interne. Pas de bypass d’authentification ; mots de passe hachés, limitation des tentatives, messages d’échec génériques et pages privées `no-store` / `noindex`. Aucune modification des rôles, suspension, suppression ou création de compte n’est exposée via le tableau de bord dans cette étape.

## Ajustement du 9 octobre 2026 — Nom de table indépendant de la marque

La table de l’entité User est désormais `user`, avec quoting explicite Doctrine. Une nouvelle migration MySQL renomme `nuru_user` en `user` sans recréer la table et sans supprimer de données ; elle préserve identifiants, hashes, rôles et clés étrangères. La migration déjà livrée Version20261009100716 demeure inchangée pour respecter les installations existantes ; sur une base neuve, l’historique crée puis renomme la table. Les requêtes SQL natives du UserRepository et les guides sont adaptés. Aucun changement de nom de site, de session, de rôle ou de base n’est imposé. Une archive différentielle fournit les fichiers à reporter sur la dernière version utilisateurs/admin.

## Extension — Inscription publique et activation contrôlée

Trois profils publics : apprenant, formateur et représentant d’établissement. Formulaire Symfony validé côté serveur, identité nom/prénom/e-mail, mot de passe confirmé (12 caractères minimum), informations pédagogiques pour le formateur et informations de l’établissement pour son représentant. Le type choisi est une liste fermée, jamais ROLE_ADMIN. Le compte institutionnel reçoit ROLE_ETABLISSEMENT et référence un nouvel établissement, sans permettre de réclamer un établissement existant.

Les nouveaux comptes sont inactifs. L’apprenant devient actif après vérification de l’adresse ; formateur et établissement exigent les deux conditions, e-mail vérifié ET décision admin approuvée. L’admin peut approuver avant ou après la vérification ; il ne peut pas contourner l’e-mail. Refus conservé avec motif et audit de décideur/date ; ne supprime pas les comptes. Les comptes CLI et existants restent classés internes, avec leurs accès actuels ; leur migration ne prétend pas qu’un e-mail a été vérifié. Le contrôle d’accès et le rafraîchissement de session appliquent les conditions d’activation.

Lien de vérification issu d’un jeton aléatoire de 256 bits, empreinte SHA-256 en base, expiration 24 h et usage unique. GET affiche une confirmation, POST CSRF marque l’adresse vérifiée (protection contre les robots de lecture de mails). Renvoi public à réponse générique, throttling IP/adresse, rotation du jeton et délai minimum. Aucun jeton ou lien de vérification n’apparaît dans une page publique, un log applicatif ou l’admin. Symfony Mailer utilise un MAILER_DSN explicite et une origine publique fixée par APP_PUBLIC_ORIGIN, jamais Host. SMTP réel ou boîte de test locale suivant le choix utilisateur ; aucun envoi réel sans configuration. En cas d’échec SMTP, le compte demeure inactif et un renvoi est possible ; ne jamais présenter l’adresse comme vérifiée.

Admin : /admin/inscriptions filtrable et paginé, /admin/inscriptions/{id} détails puis POST approbation/refus protégés CSRF et ROLE_ADMIN. Concurrence traitée par verrou DB pour une décision unique ; pas d’auto-approbation. Tableau de bord avec demandes en attente. /espace/etablissement affiche seulement l’établissement lié au compte. Formulaires et pages respectent le design ivoire/émeraude existant, labels et erreurs accessibles, sans nouveaux visuels décoratifs.

Structure : User étendu et migration additive ; src/Form/ et src/Dto/ pour les formulaires ; src/Service/RegistrationService et EmailVerificationService pour transitions/verrouillages ; src/Controller/RegistrationController et AdminRegistrationController ; templates/registration/, templates/emails/ et templates/admin/registration*. Guide de configuration locale, tests sur base de validation, archive des fichiers modifiés et projet complet sans secrets.

Choix confirmé par l’utilisateur : son serveur SMTP, configuré dans `.env.dev.local`. Aucun identifiant SMTP n’est demandé dans la conversation, aucun connecteur d’envoi personnel n’est utilisé. Le projet livre Symfony Mailer, les paramètres MAILER_DSN / MAILER_FROM / APP_PUBLIC_ORIGIN et un guide ; le sandbox valide l’envoi exclusivement avec un transport SMTP de capture localhost, sans message externe. L’utilisateur active le transport réel sur son poste avec ses propres paramètres.
