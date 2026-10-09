<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Formation;
use App\Repository\FormationRepository;

final class Catalog
{
    public const DOMAINS = DemoCatalog::DOMAINS;
    public const PROGRAMS = DemoCatalog::PROGRAMS;
    public const MODALITIES = DemoCatalog::MODALITIES;

    public function __construct(
        private readonly DemoCatalog $demo,
        private readonly FormationRepository $formations,
        private readonly string $source,
    ) {
        if (!in_array($source, ['demo', 'database'], true)) {
            throw new \InvalidArgumentException('CATALOG_SOURCE doit être demo ou database.');
        }
    }

    public function all(): array
    {
        if ($this->source === 'demo') {
            return $this->demo->all();
        }
        return array_map($this->toArray(...), $this->formations->findCatalogue([]));
    }

    public function featured(): array
    {
        if ($this->source === 'demo') {
            return $this->demo->featured();
        }
        return array_map($this->toArray(...), $this->formations->findFeatured());
    }

    public function find(string $slug): ?array
    {
        if ($this->source === 'demo') {
            return $this->demo->find($slug);
        }
        $formation = $this->formations->findOneWithContent($slug);
        return $formation === null ? null : $this->toArray($formation);
    }

    public function filter(array $filters): array
    {
        if ($this->source === 'demo') {
            return $this->demo->filter($filters);
        }
        return array_map($this->toArray(...), $this->formations->findCatalogue($filters));
    }

    private function toArray(Formation $formation): array
    {
        $result = [
            'slug' => $formation->getSlug(),
            'title' => $formation->getTitre(),
            'domain' => $formation->getDomaine(),
            'program' => $formation->getProgramme(),
            'modality' => $formation->getModalite(),
            'duration' => $formation->getDuree(),
            'hours' => $formation->getHeures(),
            'price' => $formation->getPrix(),
            'image' => $formation->getImage(),
            'provider' => $formation->getEtablissement()->getNom(),
            'teacher' => $formation->getFormateurUtilisateur()?->getNomComplet() ?? $formation->getFormateur(),
            'level' => $formation->getNiveau(),
            'description' => $formation->getDescription(),
            'objectives' => $formation->getObjectifs(),
            'modules' => array_map(
                static fn ($module): string => $module->getTitre(),
                $formation->getModules()->toArray(),
            ),
            'featured' => $formation->isMiseEnAvant(),
            'status' => $formation->getStatut(),
        ];
        if ($formation->getLieu() !== null) {
            $result['location'] = $formation->getLieu();
        }
        if ($formation->getDates() !== null) {
            $result['dates'] = $formation->getDates();
        }
        if ($formation->getPlaces() !== null) {
            $result['places'] = $formation->getPlaces();
        }
        return $result;
    }
}
