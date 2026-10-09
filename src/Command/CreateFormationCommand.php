<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Formation;
use App\Repository\EtablissementRepository;
use App\Repository\FormationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'app:formation:create', description: 'Persister une formation dans MySQL, pour un établissement déjà créé.')]
final class CreateFormationCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormationRepository $formations,
        private readonly EtablissementRepository $etablissements,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('slug', InputArgument::REQUIRED, 'Identifiant URL unique, exemple initiation-php.')
            ->addArgument('titre', InputArgument::REQUIRED, 'Titre de la formation.')
            ->addArgument('etablissement', InputArgument::REQUIRED, 'Slug d’un établissement existant.')
            ->addOption('prix', null, InputOption::VALUE_REQUIRED, 'Prix entier en GNF.', '0')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Description de la formation.', 'Formation créée depuis la console.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slug = (string) $input->getArgument('slug');
        $titre = trim((string) $input->getArgument('titre'));
        $prix = (string) $input->getOption('prix');
        if (!preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', $slug) || strlen($slug) > 255) {
            $io->error('Le slug doit contenir uniquement des lettres minuscules, chiffres et tirets, sur 255 caractères maximum.');
            return Command::INVALID;
        }
        if ($titre === '' || mb_strlen($titre) > 255) {
            $io->error('Le titre doit contenir de 1 à 255 caractères.');
            return Command::INVALID;
        }
        if (!ctype_digit($prix) || strlen($prix) > 10 || (int) $prix > 2147483647) {
            $io->error('Le prix doit être un entier compris entre 0 et 2147483647 GNF.');
            return Command::INVALID;
        }
        if ($this->formations->findOneBy(['slug' => $slug]) !== null) {
            $io->error('Ce slug est déjà utilisé. La formation existante n’a pas été modifiée.');
            return Command::INVALID;
        }
        $etablissement = $this->etablissements->findOneBy(['slug' => (string) $input->getArgument('etablissement')]);
        if ($etablissement === null) {
            $io->error('Établissement inconnu. Importez les données avec app:database:seed ou créez cet établissement via Doctrine.');
            return Command::INVALID;
        }
        $formation = new Formation($slug, $titre, $etablissement);
        $formation->setPrix((int) $prix)->setDescription((string) $input->getOption('description'));
        $this->em->persist($formation);
        $this->em->flush();
        $io->success(sprintf('Formation enregistrée : id=%d, slug=%s.', $formation->getId(), $formation->getSlug()));
        $io->text('Visible dans /formations si CATALOG_SOURCE=database. Les inscriptions restent en démonstration.');
        return Command::SUCCESS;
    }
}
