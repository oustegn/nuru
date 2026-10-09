<?php

declare(strict_types=1);

use App\Entity\User;
use App\Kernel;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
$kernel = new Kernel($_SERVER['APP_ENV'], false);
$kernel->boot();
$em = $kernel->getContainer()->get('doctrine')->getManager();
if (!preg_match('/(?:_test|_validation)$/D', (string) $em->getConnection()->getDatabase())) {
    throw new RuntimeException('Base de test dédiée requise.');
}
$input = json_decode(stream_get_contents(STDIN), true, flags: JSON_THROW_ON_ERROR);
$email = $input['email'] ?? '';
if (!is_string($email) || !preg_match('/^registration-[a-f0-9]{12}-[a-z]+@nuru\.invalid$/D', $email)) {
    throw new RuntimeException('Adresse de test invalide.');
}
$user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
$action = $input['action'] ?? 'inspect';
if ($action === 'cleanup') {
    if ($user !== null) {
        $institution = $user->getEtablissement();
        $em->remove($user);
        $em->flush();
        if ($institution !== null) {
            $em->remove($institution);
            $em->flush();
        }
    }
    echo json_encode(['cleaned' => true]);
} elseif ($action === 'resend-ready' && $user !== null) {
    $em->getConnection()->executeStatement('UPDATE `user` SET verification_envoye_le = ? WHERE id = ?', [(new DateTimeImmutable('-3 minutes'))->format('Y-m-d H:i:s'), $user->getId()]);
    echo json_encode(['ready' => true]);
} elseif ($action === 'expire' && $user !== null) {
    $em->getConnection()->executeStatement('UPDATE `user` SET verification_expire_le = ? WHERE id = ?', [(new DateTimeImmutable('-1 minute'))->format('Y-m-d H:i:s'), $user->getId()]);
    echo json_encode(['expired' => true]);
} elseif ($user !== null) {
    echo json_encode([
        'id' => $user->getId(), 'active' => $user->isActif(), 'verified' => $user->isEmailVerifie(),
        'status' => $user->getStatutValidation(), 'type' => $user->getTypeInscription(), 'roles' => $user->getRoles(),
        'hasTokenHash' => $user->getVerificationTokenHash() !== null,
        'hasPasswordHash' => (password_get_info($user->getPassword())['algoName'] ?? 'unknown') !== 'unknown',
        'institution' => $user->getEtablissement()?->getNom(), 'decisionBy' => $user->getDecisionPar()?->getId(),
        'reason' => $user->getMotifRefus(),
    ], JSON_THROW_ON_ERROR);
} else {
    echo json_encode(['absent' => true]);
}
$kernel->shutdown();
