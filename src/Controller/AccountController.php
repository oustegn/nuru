<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\FormationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

final class AccountController extends AbstractController
{
    #[Route('/mon-espace', name: 'app_account', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function index(): Response
    {
        if ($this->isGranted('ROLE_ADMIN')) {
            return $this->redirectToRoute('app_admin');
        }
        if ($this->isGranted('ROLE_ETABLISSEMENT')) {
            return $this->redirectToRoute('app_institution_account');
        }
        if ($this->isGranted('ROLE_FORMATEUR')) {
            return $this->redirectToRoute('app_teacher');
        }
        return $this->redirectToRoute('app_learner');
    }

    #[Route('/espace/apprenant', name: 'app_learner', methods: ['GET'])]
    #[IsGranted('ROLE_APPRENANT')]
    public function learner(): Response
    {
        return $this->privateResponse('account/learner.html.twig', ['section' => 'learner', 'heading' => 'Espace apprenant']);
    }

    #[Route('/espace/formateur', name: 'app_teacher', methods: ['GET'])]
    #[IsGranted('ROLE_FORMATEUR')]
    public function teacher(FormationRepository $formations): Response
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }
        return $this->privateResponse('account/teacher.html.twig', [
            'section' => 'teacher', 'heading' => 'Espace formateur',
            'courses' => $formations->findBy(['formateurUtilisateur' => $user], ['id' => 'DESC']),
        ]);
    }

    #[Route('/espace/etablissement', name: 'app_institution_account', methods: ['GET'])]
    #[IsGranted('ROLE_ETABLISSEMENT')]
    public function institution(): Response
    {
        return $this->privateResponse('account/institution.html.twig', ['section' => 'institution', 'heading' => 'Espace établissement']);
    }

    private function privateResponse(string $template, array $data): Response
    {
        $response = $this->render($template, $data);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
