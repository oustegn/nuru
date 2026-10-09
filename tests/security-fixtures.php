<?php

declare(strict_types=1);

use App\Entity\Etablissement;
use App\Entity\Formation;
use App\Entity\User;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;

require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
$name = $em->getConnection()->getDatabase();
if (!preg_match('/(?:_test|_validation)$/D', (string) $name)) {
    throw new RuntimeException('Les tests de sécurité exigent une base dédiée dont le nom finit par _test ou _validation.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$tag = $input['tag'] ?? '';
if (!is_string($tag) || !preg_match('/^[a-f0-9]{12}$/D', $tag)) {
    throw new RuntimeException('Identifiant de test invalide.');
}

if (($input['action'] ?? '') === 'disable-learner') {
    $user = $em->getRepository(User::class)->findOneBy(['email' => 'learner-'.$tag.'@nuru.invalid']);
    if ($user === null) {
        throw new RuntimeException('Compte temporaire absent.');
    }
    $user->setActif(false);
    $em->flush();
    echo json_encode(['disabled' => true]);
    $kernel->shutdown();
    exit;
}

if (($input['action'] ?? '') === 'cleanup') {
    // Supprimer uniquement les objets créés par ce test et reconnus par leur identifiant.
    $slugs = ['security-'.$tag];
    for ($index = 1; $index <= 21; ++$index) {
        $slugs[] = 'security-'.$tag.'-'.$index;
    }
    foreach ($em->getRepository(Formation::class)->findBy(['slug' => $slugs]) as $formation) {
        $em->remove($formation);
    }
    $em->flush();
    foreach (['learner', 'teacher', 'admin', 'dual', 'disabled'] as $role) {
        $user = $em->getRepository(User::class)->findOneBy(['email' => $role.'-'.$tag.'@nuru.invalid']);
        if ($user !== null) {
            $em->remove($user);
        }
    }
    foreach ($em->getRepository(Etablissement::class)->findBy(['slug' => $slugs]) as $institution) {
        $em->remove($institution);
    }
    $em->flush();
    echo json_encode(['cleaned' => true]);
    $kernel->shutdown();
    exit;
}

$hasher = new UserPasswordHasher(new PasswordHasherFactory([User::class => ['algorithm' => 'auto']]));
$credentials = [];
$users = [];
$roles = [
    'learner' => ['ROLE_APPRENANT'],
    'teacher' => ['ROLE_FORMATEUR'],
    'admin' => ['ROLE_ADMIN'],
    'dual' => ['ROLE_APPRENANT', 'ROLE_FORMATEUR'],
    'disabled' => ['ROLE_APPRENANT'],
];
foreach ($roles as $label => $userRoles) {
    $password = bin2hex(random_bytes(20));
    $user = new User($label.'-'.$tag.'@nuru.invalid', 'Test', ucfirst($label));
    $user->setRoles($userRoles)->setActif($label !== 'disabled');
    $user->setPassword($hasher->hashPassword($user, $password));
    $em->persist($user);
    $users[$label] = $user;
    $credentials[$label] = ['email' => $user->getEmail(), 'password' => $password];
}
$institution = new Etablissement('security-'.$tag, 'Établissement temporaire de sécurité');
$formation = new Formation('security-'.$tag, 'Formation temporaire de sécurité', $institution);
$formation->setFormateurUtilisateur($users['teacher']);
$em->persist($institution);
$em->persist($formation);
for ($index = 1; $index <= 21; ++$index) {
    $bulkInstitution = new Etablissement('security-'.$tag.'-'.$index, 'Établissement de pagination '.$index);
    $bulkFormation = new Formation('security-'.$tag.'-'.$index, 'Formation de pagination '.$index, $bulkInstitution);
    $em->persist($bulkInstitution);
    $em->persist($bulkFormation);
}
$em->flush();
// Consommé uniquement par le script Python ; ne jamais afficher cette sortie dans un journal.
echo json_encode(['credentials' => $credentials, 'slug' => $formation->getSlug()], JSON_THROW_ON_ERROR);
$kernel->shutdown();
