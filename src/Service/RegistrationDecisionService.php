<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;

final class RegistrationDecisionService
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function decide(int $id, User $admin, bool $approve, string $reason = ''): void
    {
        if (!in_array(User::ROLE_ADMIN, $admin->getRoles(), true) || !$admin->isActif()) {
            throw new \DomainException('Administrateur actif requis.');
        }
        $this->em->beginTransaction();
        try {
            $user = $this->em->find(User::class, $id, LockMode::PESSIMISTIC_WRITE);
            if ($user === null) {
                throw new \DomainException('Demande introuvable.');
            }
            $this->em->refresh($user);
            if (!$user->isInscriptionPublique() || !$user->requiresAdminApproval() || $user->getStatutValidation() !== 'attente') {
                throw new \DomainException('Cette inscription n’attend plus de décision.');
            }
            if ($approve) {
                $user->approveRegistration($admin);
            } else {
                $user->rejectRegistration($admin, $reason);
            }
            $user->syncRegistrationActivation();
            $this->em->flush();
            $this->em->commit();
        } catch (\Throwable $exception) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            throw $exception;
        }
    }
}
