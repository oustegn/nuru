<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(name: 'app:user:create', description: 'Créer localement un compte utilisateur avec mot de passe saisi de manière masquée.')]
final class CreateUserCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserRepository $users,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::REQUIRED, 'Adresse e-mail du compte.')
            ->addArgument('prenom', InputArgument::REQUIRED, 'Prénom du compte.')
            ->addArgument('nom', InputArgument::REQUIRED, 'Nom du compte.')
            ->addOption(
                'role',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Rôle applicatif, répétable. Sans --role : ROLE_APPRENANT.',
                [User::ROLE_APPRENANT],
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$input->isInteractive()) {
            $io->error('Création refusée : un mot de passe doit être saisi localement de manière masquée ; le mode non interactif ne peut pas créer de compte.');

            return Command::INVALID;
        }

        try {
            $user = new User(
                (string) $input->getArgument('email'),
                (string) $input->getArgument('prenom'),
                (string) $input->getArgument('nom'),
            );

            $roles = $input->getOption('role');
            if (!is_array($roles)) {
                throw new \InvalidArgumentException('Les rôles doivent être fournis sous forme de liste.');
            }
            $user->setRoles($roles);
        } catch (\InvalidArgumentException $exception) {
            $io->error($exception->getMessage());

            return Command::INVALID;
        }

        if ($this->users->findOneByNormalizedEmail($user->getEmail()) !== null) {
            $io->error('Cette adresse e-mail est déjà utilisée. Le compte existant n’a pas été modifié.');

            return Command::INVALID;
        }

        try {
            $password = $this->askHiddenWithoutFallback($io, 'Mot de passe (12 à 4096 caractères)');
            $confirmation = $this->askHiddenWithoutFallback($io, 'Confirmation du mot de passe');
        } catch (RuntimeException $exception) {
            $io->error('Impossible de lire un mot de passe de manière masquée. Aucun compte n’a été créé.');

            return Command::FAILURE;
        }

        if (!$this->isValidPasswordLength($password)) {
            $io->error('Le mot de passe doit contenir entre 12 et 4096 caractères.');

            return Command::INVALID;
        }
        if (!hash_equals($password, $confirmation)) {
            $io->error('La confirmation du mot de passe ne correspond pas.');

            return Command::INVALID;
        }

        try {
            $user->setPassword($this->passwordHasher->hashPassword($user, $password));
            unset($password, $confirmation);
            $this->em->persist($user);
            $this->em->flush();
        } catch (UniqueConstraintViolationException) {
            $io->error('Cette adresse e-mail vient déjà d’être créée. Aucun compte supplémentaire n’a été ajouté.');

            return Command::FAILURE;
        }

        $io->success(sprintf(
            'Compte créé : id=%d, email=%s, rôles=%s.',
            $user->getId(),
            $user->getEmail(),
            implode(', ', $user->getRoles()),
        ));

        return Command::SUCCESS;
    }

    private function askHiddenWithoutFallback(SymfonyStyle $io, string $prompt): string
    {
        $question = new Question($prompt.':');
        $question->setHidden(true);
        $question->setHiddenFallback(false);

        $answer = $io->askQuestion($question);
        if (!is_string($answer)) {
            throw new RuntimeException('Réponse de mot de passe invalide.');
        }

        return $answer;
    }

    private function isValidPasswordLength(string $password): bool
    {
        $length = mb_strlen($password, 'UTF-8');

        return $length >= 12 && $length <= 4096;
    }
}
