<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;

final class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    public function __construct(private readonly UrlGeneratorInterface $urls) {}

    public function authenticate(Request $request): Passport
    {
        $params = $request->request->all();
        $email = $params['email'] ?? '';
        $password = $params['password'] ?? '';
        $csrf = $params['_csrf_token'] ?? '';
        if (!is_string($email) || !is_string($password) || !is_string($csrf)) {
            throw new BadCredentialsException();
        }
        $email = mb_strtolower(trim($email));
        $request->getSession()->set(SecurityRequestAttributes::LAST_USERNAME, $email);
        return new Passport(
            new UserBadge($email),
            new PasswordCredentials($password),
            [new CsrfTokenBadge('authenticate', $csrf)],
        );
    }

    public function supports(Request $request): bool
    {
        return $request->isMethod('POST') && $request->attributes->get('_route') === 'app_login';
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // Une destination interne fixe ; aucune URL fournie par le navigateur.
        return new RedirectResponse($this->urls->generate('app_account'));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urls->generate('app_login');
    }
}
