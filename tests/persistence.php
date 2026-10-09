<?php

declare(strict_types=1);

use App\Entity\Etablissement;
use App\Entity\Formation;
use App\Entity\ModuleFormation;
use App\Kernel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
assert($em instanceof EntityManagerInterface);
$connection = $em->getConnection();
$count = 0;
function verify(bool $condition, string $message): void
{
    global $count;
    if (!$condition) {
        throw new RuntimeException('ÉCHEC : '.$message);
    }
    ++$count;
    echo 'OK : '.$message.PHP_EOL;
}

// Seuls nos nouveaux objets sont modifiés ; le ROLLBACK protège la base cible.
$connection->beginTransaction();
try {
    $suffix = bin2hex(random_bytes(6));
    $etablissement = new Etablissement('test-'.$suffix, 'Établissement de test transactionnel');
    $formation = new Formation('test-'.$suffix, 'Formation de test MySQL', $etablissement);
    $formation->setPrix(750000)->setObjectifs(['Persister avec Doctrine']);
    $formation->addModule(new ModuleFormation('Deuxième module', 2));
    $formation->addModule(new ModuleFormation('Premier module', 1));
    $em->persist($etablissement);
    $em->persist($formation);
    $em->flush();
    $id = $formation->getId();
    verify($id !== null, 'Identifiant généré par MySQL après flush');
    verify($formation->getModules()->count() === 2, 'Deux modules persistés en cascade');
    $em->clear();
    $reloaded = $em->find(Formation::class, $id);
    verify($reloaded instanceof Formation, 'Lecture depuis SQL après vidage de l’EntityManager');
    verify($reloaded->getPrix() === 750000, 'Prix entier GNF conservé');
    verify($reloaded->getObjectifs() === ['Persister avec Doctrine'], 'Objectifs JSON conservés');
    verify($reloaded->getEtablissement()->getSlug() === 'test-'.$suffix, 'Relation établissement rechargée');
    verify($reloaded->getModules()->first()->getPosition() === 1, 'Modules triés par position');
    $reloaded->setTitre('Titre mis à jour en base');
    $em->flush();
    $em->clear();
    verify($em->find(Formation::class, $id)->getTitre() === 'Titre mis à jour en base', 'Mise à jour par flush sans nouveau persist');
    $reloaded = $em->find(Formation::class, $id);
    $module = $reloaded->getModules()->first();
    $moduleId = $module->getId();
    $reloaded->removeModule($module);
    $em->flush();
    $em->clear();
    verify($em->find(ModuleFormation::class, $moduleId) === null, 'Suppression d’un module orphelin dans la transaction de test');
} finally {
    $connection->rollBack();
    $em->clear();
    $kernel->shutdown();
}
echo sprintf("%d contrôles de persistance réussis ; toutes les données de test ont été annulées.\n", $count);
