<?php

namespace App\Service;

final class DemoCatalog
{
    public const DOMAINS = ['Numérique', 'Management', 'Finance'];
    public const PROGRAMS = ['Certification', 'BTS', 'Licence', 'Master'];
    public const MODALITIES = ['En ligne', 'Présentiel', 'Hybride'];

    public function all(): array
    {
        $code = '/manus-storage/async-images/lOTTiaptubHNn5aRHM2kRv/image-1.webp';
        $business = '/manus-storage/async-images/lOTTiaptubHNn5aRHM2kRv/image-2.webp';
        $data = '/manus-storage/async-images/lOTTiaptubHNn5aRHM2kRv/image-3.webp';

        return [
            [
                'slug' => 'developpement-web-symfony', 'title' => 'Développement web avec Symfony',
                'domain' => 'Numérique', 'program' => 'Certification', 'modality' => 'En ligne',
                'duration' => '8 semaines', 'hours' => 32, 'price' => 750000, 'image' => $code,
                'provider' => 'Nuru Digital Academy', 'teacher' => 'Mamadou Diallo', 'level' => 'Intermédiaire',
                'description' => 'Passez de vos premières lignes de PHP à une application web structurée. Découvrez Symfony, Twig et les bonnes pratiques pour construire des projets solides.',
                'objectives' => ['Structurer une application Symfony', 'Créer des interfaces avec Twig', 'Concevoir des routes et des formulaires', 'Préparer le déploiement d’un projet web'],
                'modules' => ['Les fondamentaux de PHP moderne', 'Premiers pas avec Symfony', 'Twig et les interfaces web', 'Données, formulaires et validation', 'Projet pratique : votre première plateforme'],
                'featured' => true, 'status' => 'Accès à votre rythme',
            ],
            [
                'slug' => 'gestion-de-projet', 'title' => 'Les fondamentaux de la gestion de projet',
                'domain' => 'Management', 'program' => 'Certification', 'modality' => 'En ligne',
                'duration' => '6 semaines', 'hours' => 24, 'price' => 450000, 'image' => $business,
                'provider' => 'Institut Horizon', 'teacher' => 'Aïssatou Camara', 'level' => 'Débutant',
                'description' => 'Transformez vos idées en projets concrets. Apprenez à définir des objectifs, organiser une équipe et piloter votre activité avec des outils simples.',
                'objectives' => ['Définir le périmètre d’un projet', 'Organiser les étapes et les ressources', 'Animer une équipe', 'Suivre les résultats et maîtriser les risques'],
                'modules' => ['Cadrer son projet', 'Planifier et organiser', 'Collaborer efficacement', 'Budget et suivi', 'Étude de cas pratique'],
                'featured' => true, 'status' => 'Accès à votre rythme',
            ],
            [
                'slug' => 'analyse-de-donnees', 'title' => 'Analyse de données : d’Excel à Power BI',
                'domain' => 'Numérique', 'program' => 'Certification', 'modality' => 'Hybride',
                'duration' => '10 semaines', 'hours' => 40, 'price' => 900000, 'image' => $data,
                'provider' => 'Nuru Digital Academy', 'teacher' => 'Fatoumata Barry', 'level' => 'Débutant',
                'description' => 'Donnez du sens à vos données et prenez de meilleures décisions. Maîtrisez les tableaux de bord, la visualisation et les bases de l’analyse métier.',
                'objectives' => ['Nettoyer et organiser les données', 'Utiliser les fonctions clés d’Excel', 'Construire un tableau de bord Power BI', 'Présenter une analyse claire'],
                'modules' => ['Comprendre les données', 'Excel et tableaux croisés', 'Modéliser dans Power BI', 'Visualiser et raconter', 'Projet de tableau de bord'],
                'featured' => true, 'status' => 'À venir', 'location' => 'Conakry · atelier de démonstration',
                'dates' => 'Du 16 novembre 2026 au 24 janvier 2027', 'places' => 20,
            ],
            [
                'slug' => 'bts-gestion-entreprise', 'title' => 'BTS Gestion des entreprises',
                'domain' => 'Management', 'program' => 'BTS', 'modality' => 'Hybride',
                'duration' => '2 ans', 'hours' => 1200, 'price' => 6500000, 'image' => $business,
                'provider' => 'Institut Horizon', 'teacher' => 'Équipe pédagogique Horizon', 'level' => 'Post-bac',
                'description' => 'Un exemple de parcours académique professionnalisant pour comprendre le fonctionnement de l’entreprise, sa gestion et son environnement.',
                'objectives' => ['Comprendre l’organisation de l’entreprise', 'Acquérir les bases comptables', 'Développer ses compétences commerciales', 'Préparer un projet professionnel'],
                'modules' => ['Organisation et management', 'Comptabilité générale', 'Marketing et communication', 'Gestion des ressources humaines', 'Projet professionnel et stage'],
                'featured' => false, 'status' => 'À venir', 'location' => 'Conakry · campus fictif Horizon',
                'dates' => 'Rentrée de démonstration : janvier 2027', 'places' => 40,
            ],
            [
                'slug' => 'licence-informatique', 'title' => 'Licence Informatique & systèmes numériques',
                'domain' => 'Numérique', 'program' => 'Licence', 'modality' => 'Hybride',
                'duration' => '3 ans', 'hours' => 1800, 'price' => 8500000, 'image' => $code,
                'provider' => 'Université Nouvelle Afrique', 'teacher' => 'Équipe pédagogique UNA', 'level' => 'Post-bac',
                'description' => 'Un exemple de programme universitaire pour explorer la programmation, les systèmes, les réseaux et les métiers du numérique.',
                'objectives' => ['Acquérir les fondements de l’informatique', 'Développer des logiciels', 'Comprendre les réseaux et les systèmes', 'Mener un projet technique'],
                'modules' => ['Algorithmique et programmation', 'Bases de données', 'Réseaux et systèmes', 'Développement web', 'Projet de fin de parcours'],
                'featured' => false, 'status' => 'En cours', 'location' => 'Conakry · campus fictif UNA',
                'dates' => 'Cohorte de démonstration : octobre 2026 à juin 2027', 'places' => 0,
            ],
            [
                'slug' => 'master-transformation-digitale', 'title' => 'Master Management de la transformation digitale',
                'domain' => 'Management', 'program' => 'Master', 'modality' => 'En ligne',
                'duration' => '2 ans', 'hours' => 900, 'price' => 12000000, 'image' => $data,
                'provider' => 'Université Nouvelle Afrique', 'teacher' => 'Équipe pédagogique UNA', 'level' => 'Bac +3',
                'description' => 'Un exemple de programme avancé pour accompagner les organisations dans leurs projets de transformation et d’innovation numérique.',
                'objectives' => ['Définir une stratégie numérique', 'Piloter le changement', 'Exploiter les données métier', 'Concevoir un projet de transformation'],
                'modules' => ['Stratégie et innovation', 'Management du changement', 'Données et décision', 'Gouvernance numérique', 'Mémoire professionnel'],
                'featured' => false, 'status' => 'Accès à votre rythme',
            ],
            [
                'slug' => 'atelier-comptabilite', 'title' => 'Atelier pratique de comptabilité OHADA',
                'domain' => 'Finance', 'program' => 'Certification', 'modality' => 'Présentiel',
                'duration' => '3 jours', 'hours' => 18, 'price' => 350000, 'image' => $business,
                'provider' => 'Institut Horizon', 'teacher' => 'Ibrahima Soumah', 'level' => 'Débutant',
                'description' => 'Un atelier de démonstration pour découvrir les écritures comptables et organiser le suivi financier d’une petite structure.',
                'objectives' => ['Comprendre les principes comptables', 'Saisir les opérations courantes', 'Organiser les pièces justificatives', 'Lire les principaux états financiers'],
                'modules' => ['Principes et référentiel', 'Opérations courantes', 'Cas pratiques accompagnés'],
                'featured' => false, 'status' => 'Terminé', 'location' => 'Conakry · centre fictif Horizon',
                'dates' => 'Du 14 au 16 septembre 2026', 'places' => 0,
            ],
        ];
    }

    public function featured(): array
    {
        return array_values(array_filter($this->all(), static fn (array $course): bool => $course['featured']));
    }

    public function find(string $slug): ?array
    {
        foreach ($this->all() as $course) {
            if ($course['slug'] === $slug) {
                return $course;
            }
        }
        return null;
    }

    public function filter(array $filters): array
    {
        $query = mb_strtolower(trim($filters['q']));
        return array_values(array_filter($this->all(), static function (array $course) use ($filters, $query): bool {
            $matchesQuery = $query === '' || str_contains(mb_strtolower($course['title'].' '.$course['provider'].' '.$course['domain']), $query);
            return $matchesQuery
                && ($filters['domaine'] === '' || $course['domain'] === $filters['domaine'])
                && ($filters['programme'] === '' || $course['program'] === $filters['programme'])
                && ($filters['modalite'] === '' || $course['modality'] === $filters['modalite']);
        }));
    }
}
