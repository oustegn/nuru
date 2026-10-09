<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /** @return list<Formation> */
    public function findFeatured(): array
    {
        return $this->catalogueQuery()
            ->andWhere('formation.miseEnAvant = :featured')
            ->setParameter('featured', true)
            ->getQuery()->getResult();
    }

    public function findOneWithContent(string $slug): ?Formation
    {
        return $this->catalogueQuery()
            ->andWhere('formation.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()->getOneOrNullResult();
    }

    /**
     * @param array{q?: mixed, domaine?: mixed, programme?: mixed, modalite?: mixed} $filters
     * @return list<Formation>
     */
    public function findCatalogue(array $filters): array
    {
        $queryBuilder = $this->catalogueQuery();
        $query = isset($filters['q']) && is_string($filters['q']) ? trim($filters['q']) : '';
        if ($query !== '') {
            $literalQuery = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
            $lowercaseQuery = mb_strtolower($literalQuery, 'UTF-8');
            $queryBuilder
                ->andWhere("(LOWER(formation.titre) LIKE :q ESCAPE '!'"
                    . " OR LOWER(etablissement.nom) LIKE :q ESCAPE '!'"
                    . " OR LOWER(formation.domaine) LIKE :q ESCAPE '!')")
                ->setParameter('q', '%'.$lowercaseQuery.'%');
        }
        foreach (['domaine', 'programme', 'modalite'] as $field) {
            // La liste des champs SQL est fixe ; les valeurs sont toutes liées.
            if (isset($filters[$field]) && is_string($filters[$field]) && trim($filters[$field]) !== '') {
                $queryBuilder->andWhere('formation.'.$field.' = :'.$field)
                    ->setParameter($field, trim($filters[$field]));
            }
        }
        return $queryBuilder->getQuery()->getResult();
    }

    private function catalogueQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('formation')
            ->innerJoin('formation.etablissement', 'etablissement')->addSelect('etablissement')
            ->leftJoin('formation.modules', 'module')->addSelect('module')
            ->leftJoin('formation.formateurUtilisateur', 'teacher')->addSelect('teacher')
            ->orderBy('formation.id', 'ASC')->addOrderBy('module.position', 'ASC');
    }
}
