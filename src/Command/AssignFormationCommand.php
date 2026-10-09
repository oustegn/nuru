<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\FormationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:formation:assign', description: 'Affecter un formateur actif existant à une formation.')]
final class AssignFormationCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormationRepository $formations,
        private readonly UserRepository $users,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('slug', InputArgument::REQUIRED, 'Slug de la formation.')
            ->addArgument('email', InputArgument::REQUIRED, 'E-mail du formateur ou administrateur existant.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = trim((string) $input->getArgument('slug'));
        $formation = $this->formations->findOneBy(['slug' => $slug]);
        if ($formation === null) {
            $io->error('Formation introuvable.');

            return Command::INVALID;
        }

        $user = $this->users->findOneByNormalizedEmail((string) $input->getArgument('email'));
        if ($user === null) {
            $io->error('Utilisateur introuvable.');

            return Command::INVALID;
        }
        if (!$user->isActif()) {
            $io->error('Ce compte est inactif et ne peut pas être affecté à une formation.');

            return Command::INVALID;
        }

        $roles = $user->getRoles();
        if (!in_array(User::ROLE_FORMATEUR, $roles, true) && !in_array(User::ROLE_ADMIN, $roles, true)) {
            $io->error('Cet utilisateur doit déjà avoir le rôle ROLE_FORMATEUR ou ROLE_ADMIN. Aucun rôle n’a été modifié.');

            return Command::INVALID;
        }

        $formation->setFormateurUtilisateur($user);
        $this->em->flush();

        $io->success(sprintf(
            'Responsable de la formation « %s » : %s.',
            $formation->getSlug(),
            $user->getNomComplet(),
        ));

        return Command::SUCCESS;
    }
}
