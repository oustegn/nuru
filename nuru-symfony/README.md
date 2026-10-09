# Nuru — Template de plateforme de formation

Un template **Symfony 7.4 LTS + Twig**, responsive et en français, inspiré du projet de plateforme de formation en Guinée et en Afrique francophone. « Nuru » est un nom de travail remplaçable.

## Extension MySQL / Doctrine

La version du 9 octobre 2026 inclut les entités `Etablissement`, `Formation` et `ModuleFormation`, leurs repositories, les migrations et des commandes de persistance. Consultez [le guide MySQL local](docs/mysql-local.md) pour créer la base `nuru`, configurer `.env.dev` et activer `CATALOG_SOURCE=database`. Le mode `demo` reste disponible sans serveur MySQL.

## Ce que contient le template

L’accueil éditorial présente les formations professionnelles et les parcours BTS, Licence et Master. Le catalogue propose une recherche et des filtres fonctionnels. Chaque formation dispose d’une fiche avec objectifs, modules, durée et prix d’exemple en GNF ; les sessions en présentiel affichent leur lieu, dates, places et statut. Une page présente les établissements et le modèle partenaire **85 % / 15 %**.

Trois espaces métier sont navigables : apprenant, formateur et établissement. Ils illustrent progression, cours, ressources, quiz, revenus, programmes et candidatures. Un mini-quiz Twig fournit un retour immédiat, sans enregistrer le résultat.

**Ce n’est pas un LMS de production.** Tous les profils, établissements, programmes, montants et indicateurs sont fictifs. Les vidéos, documents et certificats sont présentés sous forme d’aperçus explicatifs. Aucun compte sécurisé, paiement, inscription effective, candidature officielle ou diplôme réel n’est créé. Les boutons illustratifs ouvrent un dialogue qui l’indique. Le catalogue pédagogique peut désormais être persisté en MySQL, sans rendre réels les autres processus métier.

## Installation locale

Prérequis : **PHP 8.2 ou supérieur**, Composer 2 et extensions ctype, iconv, PDO MySQL, mbstring et XML/DOM. Le template a été exécuté avec PHP 8.3. Les images sont incluses ; aucun serveur Node n’est nécessaire. Un serveur MySQL est nécessaire en mode `database`, mais pas en mode `demo`.

Depuis le dossier extrait :

```bash
composer install
cp .env.example .env
php -r '$p=".env"; file_put_contents($p, str_replace("replace-this-template-secret", bin2hex(random_bytes(32)), file_get_contents($p)));'
php -S 127.0.0.1:3000 -t public router.php
```

Ouvrez `http://127.0.0.1:3000`. Sous Windows, copiez `.env.example` vers `.env` avec votre explorateur si la commande `cp` n’est pas disponible. Le serveur PHP intégré est **réservé au développement**.

## Les pages

| Route | Contenu |
| --- | --- |
| `/` | Accueil |
| `/formations` | Catalogue et filtres GET |
| `/formations/{slug}` | Fiche de formation ; 404 si inconnue |
| `/etablissements` | Présentation des espaces partenaires |
| `/demo/apprenant` | Progression, cours et mini-quiz |
| `/demo/formateur` | Formations, apprenants et revenus fictifs |
| `/demo/etablissement` | Programmes et candidatures fictives |
| `/_app/health` | Santé technique JSON |
| `/manus-routes.json` | Manifeste des pages pour Manus |

Exemples de filtres : `/formations?programme=BTS`, `/formations?domaine=Numérique`, `/formations?modalite=Présentiel`, `/formations?q=Symfony`. Les espaces `/demo/` renvoient `noindex, nofollow` et ne constituent **pas** une zone protégée par authentification.

## Personnalisation

| Élément | Fichiers principaux |
| --- | --- |
| Couleurs, espacements, polices et responsive | `public/assets/css/app.css` ; variables en tête de fichier |
| Nom, navigation, pied de page et métadonnées | `templates/base.html.twig` |
| Contenu de l’accueil | `templates/home/index.html.twig` |
| Catalogue, prix, objectifs et modules | `src/Service/DemoCatalog.php` |
| Profils et statistiques de démonstration | `templates/demo/*.html.twig` |
| Comportements des dialogues, menus et quiz | `public/assets/js/app.js` |
| Logo et favicon | `public/assets/images/logo.svg` |
| Visuels d’illustration | `public/manus-storage/async-images/` |
| Routage et rendu | `src/Controller/WebsiteController.php` |

Les photographies sont des illustrations originales générées ; elles ne représentent pas de personnes ou de partenaires identifiés. Les chemins `/manus-storage/…` restent compatibles avec le stockage Manus. Leurs copies dans `public/manus-storage/` permettent aussi l’utilisation autonome du template hors de Manus.

Les titres utilisent **Fraunces**, les interfaces **Plus Jakarta Sans**, chargées depuis Google Fonts. Des polices système prennent le relais si le service est inaccessible. Pour un site sans dépendance externe, vous pouvez auto-héberger les fichiers de police et adapter les déclarations CSS.

### Domaine public et référencement

Laissez `SITE_PUBLIC_ORIGIN` vide pour un usage local. Sur votre domaine final, renseignez une origine HTTPS **sans barre oblique finale**, par exemple `https://formation.example.org`. Symfony générera les balises canonical et de partage à partir de cette valeur explicite, jamais à partir d’un hôte interne.

Le contenu et les métadonnées sont rendus côté serveur. Dans Manus, sitemap et robots sont gérés par la passerelle ; pour un hébergement indépendant, ajoutez ces documents selon votre politique d’indexation et excluez les espaces de démonstration.

## Vérifications

```bash
composer validate --strict
php bin/console lint:yaml config
php bin/console lint:twig templates
php bin/console debug:router
node --check public/assets/js/app.js   # facultatif ; nécessite Node
php tests/check.php                   # serveur local sur 3000 déjà démarré
php tests/persistence.php             # MySQL configuré et migrations appliquées
```

Le script de contrôle vérifie le catalogue, ses filtres, les pages HTTP, les vraies 404, les balises des démonstrations, les images locales et le manifeste de routes. Il utilise les fonctions standard de PHP, sans dépendance de test supplémentaire.

## Hébergement PHP en conteneur

Le `Dockerfile` prépare **Apache + PHP 8.3**, installe les dépendances verrouillées et sert uniquement `public/`. Le point d’entrée respecte `PORT` et écoute sur 3000 par défaut. Les réécritures Apache dirigent les pages vers Symfony. Le contrat Manus utilise `/_app/health` pour vérifier le démarrage.

```bash
docker build -t nuru-template .
docker run --rm -p 3000:3000 \
  -e APP_SECRET="$(php -r 'echo bin2hex(random_bytes(32));')" \
  -e SITE_PUBLIC_ORIGIN=https://formation.example.org \
  nuru-template
```

Définissez `APP_SECRET` de façon stable dans votre hébergement. Le conteneur refuse de démarrer si cette variable n’est pas fournie. Dans un déploiement Manus, ajoutez cette variable dans les paramètres sécurisés du projet avant de publier. Utilisez HTTPS en production. Le conteneur est préparé par le code ; sa construction et son déploiement hébergé ne sont pas présentés comme déjà effectués.

## Passage à une plateforme réelle

Avant une utilisation réelle, étendre la persistance Doctrine aux autres objets métier, intégrer les comptes et rôles, les permissions des établissements, les inscriptions et admissions, le stockage protégé des vidéos et documents, les prestataires de paiement mobile et leurs webhooks, les conditions de certification et la conformité réglementaire. La validation académique, le jury et la délivrance des diplômes restent sous la responsabilité des établissements partenaires.

N’utilisez pas les données fictives comme offre commerciale ni les profils de démonstration comme dossiers officiels. Les modules de l’application mobile et du téléchargement hors ligne ne sont pas inclus dans ce template web.

## État de livraison

Symfony 7.4.20 a été exécuté avec PHP 8.3. Les 60 contrôles HTTP et les 9 contrôles transactionnels de persistance MySQL, la syntaxe PHP/JavaScript, les configurations YAML, les templates Twig et l’injection de dépendances passent. Les réponses de l’aperçu ont été vérifiées localement et publiquement. Le conteneur n’a pas été publié : un secret stable et, en mode database, une base accessible doivent être configurés avant publication.
