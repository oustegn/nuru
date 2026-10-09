<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use InvalidArgumentException;
use JsonException;

/** @extends ServiceEntityRepository<User> */
class UserRepository extends ServiceEntityRepository
{
    private const PAGE_SIZE = 20;
    private const MAX_PAGE = 100000;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * @return array{items:list<User>, total:int, page:int, pages:int}
     */
    public function findAdminPage(int $page, string $q = ''): array
    {
        $requestedPage = max(1, min(self::MAX_PAGE, $page));
        $queryBuilder = $this->createQueryBuilder('user');

        $query = trim($q);
        if ($query !== '') {
            $escapedQuery = str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $query);
            $queryBuilder
                ->andWhere("(LOWER(user.nom) LIKE :q ESCAPE '!'"
                    . " OR LOWER(user.prenom) LIKE :q ESCAPE '!'"
                    . " OR LOWER(user.email) LIKE :q ESCAPE '!')")
                ->setParameter('q', '%'.mb_strtolower($escapedQuery, 'UTF-8').'%');
        }

        $countQueryBuilder = clone $queryBuilder;
        $total = (int) $countQueryBuilder
            ->resetDQLPart('select')
            ->select('COUNT(user.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $actualPage = min($requestedPage, $pages);

        /** @var list<User> $items */
        $items = $queryBuilder
            ->orderBy('user.id', 'DESC')
            ->setFirstResult(($actualPage - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();

        return [
            'items' => $items,
            'total' => $total,
            'page' => $actualPage,
            'pages' => $pages,
        ];
    }

    public function findOneByNormalizedEmail(string $email): ?User
    {
        /** @var User|null $user */
        $user = $this->findOneBy(['email' => User::normalizeEmail($email)]);

        return $user;
    }

    public function registrationPage(int $page, string $status = 'attente'): array
    {
        if (!in_array($status, ['attente', 'approuve', 'refuse', 'tous'], true)) {
            $status = 'attente';
        }
        $qb = $this->createQueryBuilder('u')
            ->andWhere('u.typeInscription IN (:types)')->setParameter('types', ['formateur', 'etablissement']);
        if ($status !== 'tous') {
            $qb->andWhere('u.statutValidation = :status')->setParameter('status', $status);
        }
        $counter = clone $qb;
        $total = (int) $counter->select('COUNT(u.id)')->getQuery()->getSingleScalarResult();
        $pages = max(1, (int) ceil($total / 20));
        $page = max(1, min($page, $pages));
        $items = $qb->leftJoin('u.etablissement', 'institution')->addSelect('institution')
            ->orderBy('u.id', 'ASC')->setFirstResult(($page - 1) * 20)->setMaxResults(20)->getQuery()->getResult();
        return ['items' => $items, 'total' => $total, 'page' => $page, 'pages' => $pages, 'status' => $status];
    }

    public function countPendingRegistrations(): int
    {
        return (int) $this->createQueryBuilder('u')->select('COUNT(u.id)')
            ->andWhere('u.typeInscription IN (:types)')->setParameter('types', ['formateur', 'etablissement'])
            ->andWhere('u.statutValidation = :status')->setParameter('status', 'attente')->getQuery()->getSingleScalarResult();
    }

    /**
     * @throws InvalidArgumentException When the role is not one of the fixed application roles.
     */
    public function countByRole(string $role): int
    {
        if (!User::isAllowedRole($role)) {
            throw new InvalidArgumentException('Rôle inconnu pour les statistiques utilisateur.');
        }

        try {
            $roleJson = json_encode($role, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Rôle impossible à encoder pour les statistiques.', previous: $exception);
        }

        return (int) $this->getEntityManager()
            ->getConnection()
            ->fetchOne(
                'SELECT COUNT(*) FROM `user` WHERE JSON_CONTAINS(roles, :role)',
                ['role' => $roleJson],
            );
    }
}
