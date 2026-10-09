<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\RegistrationData;
use App\Entity\Etablissement;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class RegistrationService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $hasher,
    ) {}

    public function register(RegistrationData $data, string $profile): ?User
    {
        if (!in_array($profile, ['apprenant', 'formateur', 'etablissement'], true)) {
            throw new \InvalidArgumentException('Profil d’inscription inconnu.');
        }
        if ($this->users->findOneByNormalizedEmail($data->email) !== null) {
            $data->plainPassword = '';
            return null;
        }
        $user = new User($data->email, $data->prenom, $data->nom);
        $user->beginPublicRegistration($profile);
        $user->setPassword($this->hasher->hashPassword($user, $data->plainPassword));
        $data->plainPassword = '';
        $user->setTelephone($data->telephone);
        if ($profile === 'formateur') {
            $user->setSpecialite($data->specialite);
            $user->setPresentation($data->presentation);
        }
        $this->em->beginTransaction();
        try {
            if ($profile === 'etablissement') {
                // Pas de sélection d’un établissement existant : aucune revendication de propriété.
                $institution = new Etablissement('inscription-'.bin2hex(random_bytes(12)), trim($data->etablissementNom));
                $institution->setAdresse($data->etablissementAdresse)->setVille($data->etablissementVille)->setSiteWeb($data->etablissementSite);
                $user->setEtablissement($institution);
                $this->em->persist($institution);
            }
            $this->em->persist($user);
            $this->em->flush();
            $this->em->commit();
            return $user;
        } catch (UniqueConstraintViolationException) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            return null;
        } catch (\Throwable $exception) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            throw $exception;
        }
    }
}
