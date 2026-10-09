<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\Etablissement;
use App\Entity\Formation;
use App\Entity\ModuleFormation;
use App\Repository\EtablissementRepository;
use App\Repository\FormationRepository;
use App\Service\DemoCatalog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(name: 'app:database:seed', description: 'Ajouter les formations fictives absentes sans modifier les données existantes.')]
final class SeedDatabaseCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly FormationRepository $formations,
        private readonly EtablissementRepository $etablissements,
        private readonly DemoCatalog $demo,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slugger = new AsciiSlugger();
        $providers = [];
        $created = 0;
        $skipped = 0;
        foreach ($this->demo->all() as $row) {
            if ($this->formations->findOneBy(['slug' => $row['slug']]) !== null) {
                ++$skipped;
                continue;
            }
            $providerSlug = $slugger->slug($row['provider'])->lower()->toString();
            $provider = $providers[$providerSlug] ?? $this->etablissements->findOneBy(['slug' => $providerSlug]);
            if ($provider === null) {
                $provider = new Etablissement($providerSlug, $row['provider']);
                $this->em->persist($provider);
            }
            $providers[$providerSlug] = $provider;
            $formation = new Formation($row['slug'], $row['title'], $provider);
            $formation->setDomaine($row['domain'])
                ->setProgramme($row['program'])
                ->setModalite($row['modality'])
                ->setDuree($row['duration'])
                ->setHeures($row['hours'])
                ->setPrix($row['price'])
                ->setImage($row['image'])
                ->setFormateur($row['teacher'])
                ->setNiveau($row['level'])
                ->setDescription($row['description'])
                ->setObjectifs($row['objectives'])
                ->setMiseEnAvant($row['featured'])
                ->setStatut($row['status'])
                ->setLieu($row['location'] ?? null)
                ->setDates($row['dates'] ?? null)
                ->setPlaces($row['places'] ?? null);
            foreach ($row['modules'] as $index => $title) {
                $formation->addModule(new ModuleFormation($title, $index + 1));
            }
            $this->em->persist($formation);
            ++$created;
        }
        // Un seul flush : Doctrine valide l’ensemble des INSERT dans une transaction.
        $this->em->flush();
        $io->success(sprintf('%d formations ajoutées ; %d déjà présentes et laissées intactes.', $created, $skipped));
        $io->note('Les données importées sont fictives. Aucun compte, paiement ou dossier officiel n’est créé.');
        return Command::SUCCESS;
    }
}
