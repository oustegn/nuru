<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Etablissement;
use App\Entity\Formation;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin', methods: ['GET'])]
    public function dashboard(EntityManagerInterface $em, UserRepository $users): Response
    {
        return $this->privateResponse('admin/dashboard.html.twig', [
            'section' => 'admin', 'heading' => 'Administration',
            'counts' => [
                'users' => $users->count([]),
                'courses' => $em->getRepository(Formation::class)->count([]),
                'institutions' => $em->getRepository(Etablissement::class)->count([]),
                'learners' => $users->countByRole('ROLE_APPRENANT'),
                'teachers' => $users->countByRole('ROLE_FORMATEUR'),
                'admins' => $users->countByRole('ROLE_ADMIN'),
                'pending' => $users->countPendingRegistrations(),
            ],
            'recent_users' => $users->findBy([], ['id' => 'DESC'], 5),
        ]);
    }

    #[Route('/admin/utilisateurs', name: 'app_admin_users', methods: ['GET'])]
    public function users(Request $request, UserRepository $users): Response
    {
        $q = $this->query($request);
        return $this->privateResponse('admin/users.html.twig', [
            'section' => 'users', 'heading' => 'Utilisateurs', 'q' => $q,
            'pagination' => $users->findAdminPage($this->page($request), $q),
        ]);
    }

    #[Route('/admin/formations', name: 'app_admin_courses', methods: ['GET'])]
    public function courses(Request $request, EntityManagerInterface $em): Response
    {
        $qb = $em->getRepository(Formation::class)->createQueryBuilder('item')
            ->innerJoin('item.etablissement', 'institution')->addSelect('institution')
            ->leftJoin('item.formateurUtilisateur', 'teacher')->addSelect('teacher')
            ->orderBy('item.id', 'DESC');
        return $this->privateResponse('admin/courses.html.twig', [
            'section' => 'courses', 'heading' => 'Formations',
            'pagination' => $this->paginate($qb, $this->page($request)),
        ]);
    }

    #[Route('/admin/etablissements', name: 'app_admin_institutions', methods: ['GET'])]
    public function institutions(Request $request, EntityManagerInterface $em): Response
    {
        $qb = $em->getRepository(Etablissement::class)->createQueryBuilder('item')->orderBy('item.id', 'DESC');
        return $this->privateResponse('admin/institutions.html.twig', [
            'section' => 'institutions', 'heading' => 'Établissements',
            'pagination' => $this->paginate($qb, $this->page($request)),
        ]);
    }

    private function paginate(\Doctrine\ORM\QueryBuilder $qb, int $page): array
    {
        $query = $qb->getQuery();
        $paginator = new Paginator($query, false);
        $total = count($paginator);
        $pages = max(1, (int) ceil($total / 20));
        $page = min($page, $pages);
        $query->setFirstResult(($page - 1) * 20)->setMaxResults(20);
        return ['items' => iterator_to_array($paginator), 'total' => $total, 'page' => $page, 'pages' => $pages];
    }

    private function page(Request $request): int
    {
        $value = $request->query->all()['page'] ?? '1';
        return is_scalar($value) ? max(1, min(100000, (int) $value)) : 1;
    }

    private function query(Request $request): string
    {
        $value = $request->query->all()['q'] ?? '';
        return is_string($value) ? mb_substr(trim($value), 0, 200) : '';
    }

    private function privateResponse(string $template, array $data): Response
    {
        $response = $this->render($template, $data);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
