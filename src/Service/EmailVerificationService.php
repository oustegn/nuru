<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use DateTimeImmutable;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;

final class EmailVerificationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly MailerInterface $mailer,
        private readonly UrlGeneratorInterface $urls,
        private readonly Environment $twig,
        private readonly string $publicOrigin,
        private readonly string $fromAddress,
        private readonly string $mailerDsn,
    ) {}

    public function send(User $user): bool
    {
        // Un transport désactivé n’est jamais présenté comme un e-mail envoyé.
        if (!$this->isConfigured()) {
            return false;
        }
        $token = bin2hex(random_bytes(32));
        $now = new DateTimeImmutable();
        $this->em->beginTransaction();
        try {
            $locked = $this->em->find(User::class, $user->getId(), LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($locked);
            if (!$locked->isInscriptionPublique() || $locked->isEmailVerifie() || $locked->getStatutValidation() === 'refuse'
                || ($locked->getVerificationEnvoyeLe() !== null && $locked->getVerificationEnvoyeLe() > $now->modify('-2 minutes'))) {
                $this->em->commit();
                return false;
            }
            $locked->setVerificationToken(hash('sha256', $token), $now->modify('+24 hours'), $now);
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $exception) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            throw $exception;
        }
        $link = rtrim($this->publicOrigin, '/').$this->urls->generate('app_verify_email', ['token' => $token]);
        $email = (new Email())->from($this->fromAddress)->to($locked->getEmail())
            ->subject('Confirmez votre adresse e-mail — Formation')
            ->text("Bonjour ".$locked->getPrenom().",\n\nConfirmez votre adresse via ce lien valable 24 heures :\n".$link."\n\nUn formateur ou un établissement doit également être approuvé par un administrateur avant de pouvoir se connecter.\n\nSi vous n’avez pas demandé ce compte, ignorez ce message.")
            ->html($this->twig->render('emails/verify.html.twig', ['user' => $locked, 'verification_url' => $link]));
        try {
            $this->mailer->send($email);
            return true;
        } catch (TransportExceptionInterface) {
            // Le compte reste inactif. Le renvoi pourra réessayer après le délai.
            return false;
        }
    }

    public function isValidToken(string $token): bool
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return false;
        }
        $user = $this->em->getRepository(User::class)->findOneBy(['verificationTokenHash' => hash('sha256', $token)]);
        return $this->eligible($user);
    }

    public function confirm(string $token): string
    {
        if (!preg_match('/^[a-f0-9]{64}$/D', $token)) {
            return 'invalid';
        }
        $this->em->beginTransaction();
        try {
            $user = $this->em->getRepository(User::class)->findOneBy(['verificationTokenHash' => hash('sha256', $token)]);
            if ($user === null) {
                $this->em->commit();
                return 'invalid';
            }
            $this->em->lock($user, LockMode::PESSIMISTIC_WRITE);
            $this->em->refresh($user);
            if (!$this->eligible($user) || !hash_equals((string) $user->getVerificationTokenHash(), hash('sha256', $token))) {
                $this->em->commit();
                return 'invalid';
            }
            $user->markEmailVerified();
            $user->syncRegistrationActivation();
            $this->em->flush();
            $this->em->commit();
            return $user->isActif() ? 'active' : 'pending';
        } catch (\Throwable $exception) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            throw $exception;
        }
    }

    private function eligible(?User $user): bool
    {
        return $user !== null && $user->isInscriptionPublique() && !$user->isEmailVerifie()
            && $user->getStatutValidation() !== 'refuse'
            && $user->getVerificationExpireLe() !== null && $user->getVerificationExpireLe() > new DateTimeImmutable();
    }

    public function isConfigured(): bool
    {
        return !str_starts_with($this->mailerDsn, 'null:') && $this->validOrigin();
    }

    private function validOrigin(): bool
    {
        $parts = parse_url($this->publicOrigin);
        if ($parts === false || !isset($parts['scheme'], $parts['host']) || (isset($parts['user']) || isset($parts['pass']))
            || isset($parts['query']) || isset($parts['fragment']) || !in_array($parts['path'] ?? '', ['', '/'], true)) {
            return false;
        }
        return $parts['scheme'] === 'https'
            || ($parts['scheme'] === 'http' && in_array($parts['host'], ['127.0.0.1', 'localhost', '[::1]'], true));
    }
}
