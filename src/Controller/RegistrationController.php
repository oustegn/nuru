<?php

declare(strict_types=1);

namespace App\Controller;

use App\Dto\RegistrationData;
use App\Form\RegistrationType;
use App\Form\ResendVerificationType;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use App\Service\RegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'app_registration_choice', methods: ['GET'])]
    public function choice(): Response
    {
        return $this->page('registration/choice.html.twig');
    }

    #[Route('/inscription/{profile}', name: 'app_register', requirements: ['profile' => 'apprenant|formateur|etablissement'], methods: ['GET', 'POST'])]
    public function register(string $profile, Request $request, RegistrationService $registration, EmailVerificationService $verification, RateLimiterFactory $registrationIpLimiter): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('app_account');
        }
        $data = new RegistrationData();
        $form = $this->createForm(RegistrationType::class, $data, ['profile' => $profile]);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if (!$registrationIpLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted()) {
                return $this->page('registration/limited.html.twig', [], 429);
            }
            $user = $registration->register($data, $profile);
            $sent = $verification->isConfigured();
            if ($user !== null && $sent) {
                $sent = $verification->send($user);
            }
            $request->getSession()->set('registration_delivery', $sent ? 'sent' : 'unavailable');
            return $this->redirectToRoute('app_registration_sent');
        }
        return $this->page('registration/form.html.twig', ['form' => $form->createView(), 'profile' => $profile], $form->isSubmitted() ? 422 : 200);
    }

    #[Route('/inscription/confirmation', name: 'app_registration_sent', priority: 10, methods: ['GET'])]
    public function sent(Request $request): Response
    {
        $delivery = $request->getSession()->remove('registration_delivery');
        return $this->page('registration/sent.html.twig', ['delivery' => $delivery]);
    }

    #[Route('/verification-email/renvoyer', name: 'app_resend_verification', priority: 10, methods: ['GET', 'POST'])]
    public function resend(Request $request, UserRepository $users, EmailVerificationService $verification, RateLimiterFactory $verificationResendIpLimiter, RateLimiterFactory $verificationResendEmailLimiter): Response
    {
        $form = $this->createForm(ResendVerificationType::class);
        $form->handleRequest($request);
        $done = false;
        if ($form->isSubmitted() && $form->isValid()) {
            $email = mb_strtolower(trim($form->get('email')->getData()));
            $ipAllowed = $verificationResendIpLimiter->create($request->getClientIp() ?? 'unknown')->consume()->isAccepted();
            $mailAllowed = $verificationResendEmailLimiter->create(hash('sha256', $email))->consume()->isAccepted();
            if ($ipAllowed && $mailAllowed) {
                $user = $users->findOneByNormalizedEmail($email);
                if ($user !== null) {
                    $verification->send($user);
                }
            }
            $done = true; // Réponse identique pour adresse inconnue, vérifiée, refusée ou limitée.
        }
        return $this->page('registration/resend.html.twig', ['form' => $form->createView(), 'done' => $done]);
    }

    #[Route('/verification-email/{token}', name: 'app_verify_email', requirements: ['token' => '[a-f0-9]{64}'], methods: ['GET', 'POST'])]
    public function verify(string $token, Request $request, EmailVerificationService $verification): Response
    {
        if ($request->isMethod('POST')) {
            $csrf = $request->request->all()['_csrf_token'] ?? '';
            if (!is_string($csrf) || !$this->isCsrfTokenValid('verify_email_'.hash('sha256', $token), $csrf)) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Confirmation invalide.');
            }
            $result = $verification->confirm($token);
            return $this->page('registration/verified.html.twig', ['result' => $result], $result === 'invalid' ? 410 : 200);
        }
        $valid = $verification->isValidToken($token);
        return $this->page('registration/verify.html.twig', ['valid' => $valid, 'verification_csrf_id' => 'verify_email_'.hash('sha256', $token)], $valid ? 200 : 410);
    }

    private function page(string $template, array $data = [], int $status = 200): Response
    {
        $response = $this->render($template, $data, new Response(status: $status));
        $response->headers->set('Cache-Control', 'private, no-store');
        $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        $response->headers->set('Referrer-Policy', 'no-referrer');
        return $response;
    }
}
