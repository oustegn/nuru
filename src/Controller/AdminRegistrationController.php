<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\RegistrationDecisionService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminRegistrationController extends AbstractController
{
    #[Route('/admin/inscriptions', name: 'app_admin_registrations', methods: ['GET'])]
    public function index(Request $request, UserRepository $users): Response
    {
        $params = $request->query->all();
        $status = is_string($params['statut'] ?? null) ? $params['statut'] : 'attente';
        $page = is_scalar($params['page'] ?? null) ? (int) $params['page'] : 1;
        return $this->page('admin/registrations.html.twig', ['section' => 'registrations', 'heading' => 'Inscriptions à valider', 'pagination' => $users->registrationPage($page, $status)]);
    }

    #[Route('/admin/inscriptions/{id}', name: 'app_admin_registration', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function detail(int $id, UserRepository $users): Response
    {
        $applicant = $users->find($id);
        if ($applicant === null || !$applicant->requiresAdminApproval()) {
            throw $this->createNotFoundException('Demande introuvable.');
        }
        return $this->page('admin/registration_detail.html.twig', ['section' => 'registrations', 'heading' => 'Examiner une inscription', 'applicant' => $applicant]);
    }

    #[Route('/admin/inscriptions/{id}/{decision}', name: 'app_admin_registration_decide', requirements: ['id' => '\d+', 'decision' => 'approuver|refuser'], methods: ['POST'])]
    public function decide(int $id, string $decision, Request $request, RegistrationDecisionService $decisions): Response
    {
        $params = $request->request->all();
        $csrf = $params['_csrf_token'] ?? '';
        if (!is_string($csrf) || !$this->isCsrfTokenValid('registration_decision_'.$id.'_'.$decision, $csrf)) {
            throw $this->createAccessDeniedException('Décision invalide.');
        }
        $admin = $this->getUser();
        if (!$admin instanceof User) {
            throw $this->createAccessDeniedException();
        }
        $reason = is_string($params['motif'] ?? null) ? trim($params['motif']) : '';
        try {
            $decisions->decide($id, $admin, $decision === 'approuver', $reason);
            $this->addFlash('success', $decision === 'approuver' ? 'Inscription approuvée. Le compte ne sera actif que si l’e-mail est vérifié.' : 'Inscription refusée. Le compte reste inactif.');
        } catch (\DomainException|\InvalidArgumentException|\LogicException $exception) {
            $this->addFlash('error', $exception->getMessage());
        }
        return $this->redirectToRoute('app_admin_registration', ['id' => $id]);
    }

    private function page(string $template, array $data): Response
    {
        $response = $this->render($template, $data);
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        return $response;
    }
}
