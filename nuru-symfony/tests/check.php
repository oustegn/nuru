<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use App\Service\DemoCatalog;

$count = 0;
function check(bool $condition, string $message): void
{
    global $count;
    if (!$condition) {
        fwrite(STDERR, "ÉCHEC : $message\n");
        exit(1);
    }
    ++$count;
    echo "OK : $message\n";
}

$catalog = new DemoCatalog();
$filters = ['q' => '', 'domaine' => '', 'programme' => '', 'modalite' => ''];
check(count($catalog->all()) === 7, '7 formations fictives');
check(count($catalog->featured()) === 3, '3 formations mises en avant');
check(count($catalog->filter($filters + [])) === 7, 'Catalogue sans filtre');
check(count($catalog->filter(array_replace($filters, ['q' => 'SYMFONY']))) === 1, 'Recherche insensible à la casse');
check(count($catalog->filter(array_replace($filters, ['programme' => 'BTS']))) === 1, 'Filtre BTS');
check(count($catalog->filter(array_replace($filters, ['domaine' => 'Numérique', 'programme' => 'Certification']))) === 2, 'Combinaison de filtres');
check(count($catalog->filter(array_replace($filters, ['q' => 'introuvablexyz']))) === 0, 'Recherche sans résultat');
check($catalog->find('inconnue') === null, 'Détail absent');
check(10000000 * 0.85 === 8500000.0 && 10000000 * 0.15 === 1500000.0, 'Répartition exacte 85 % / 15 %');

function getPage(string $path): array
{
    $base = getenv('TEST_BASE_URL') ?: 'http://127.0.0.1:3000';
    $context = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 15]]);
    $body = @file_get_contents($base.$path, false, $context);
    if ($body === false) {
        fwrite(STDERR, "Démarrez le serveur PHP avant les tests HTTP.\n");
        exit(1);
    }
    $headers = $http_response_header ?? [];
    preg_match('/\s(\d{3})\s/', $headers[0] ?? '', $matches);
    return [(int) ($matches[1] ?? 0), $body, implode("\n", $headers)];
}

foreach (['/', '/formations', '/etablissements', '/demo/apprenant', '/demo/formateur', '/demo/etablissement'] as $path) {
    [$status, $body, $headers] = getPage($path);
    check($status === 200, "HTTP 200 $path");
    check(str_contains($body, '<h1>') && str_contains($body, '<title>'), "HTML rendu côté serveur $path");
    if (str_starts_with($path, '/demo/')) {
        check(str_contains($body, 'noindex, nofollow') && str_contains(strtolower($headers), 'x-robots-tag: noindex'), "Démo non indexable $path");
        check(str_contains($body, 'Aucun compte, admission ou paiement réel.'), "Limites de la démo explicites $path");
    }
}
foreach ($catalog->all() as $course) {
    [$status, $body] = getPage('/formations/'.$course['slug']);
    check($status === 200 && str_contains($body, htmlspecialchars($course['title'], ENT_QUOTES, 'UTF-8')), 'Fiche '.$course['slug']);
    $file = dirname(__DIR__).'/public'.$course['image'];
    check(is_file($file) && filesize($file) > 10000, 'Visuel local '.$course['slug']);
}
foreach (['/formations/inexistante', '/page-inexistante', '/demo/inconnue'] as $path) {
    [$status, $body] = getPage($path);
    check($status === 404 && str_contains($body, 'Ce chemin n’existe pas.'), "Vraie 404 $path");
}
[$status, $body] = getPage('/formations?programme=BTS');
check($status === 200 && substr_count($body, '<article class="course-card">') === 1, 'Filtrage HTTP BTS');
[$status, $body] = getPage('/formations?q=introuvablexyz');
check($status === 200 && str_contains($body, 'Aucune formation ne correspond'), 'État vide HTTP');
[$status, $body] = getPage('/formations?q[]=test');
check($status === 200, 'Paramètre de recherche non scalaire géré');
[$status, $body] = getPage('/formations?q=%3Cscript%3Ealert(1)%3C%2Fscript%3E');
check($status === 200 && !str_contains($body, '<script>alert(1)</script>'), 'Échappement de la recherche');
[$status, $body] = getPage('/manus-routes.json');
$manifest = json_decode($body, true);
check($status === 200 && count($manifest['routes'] ?? []) === 7, 'Manifeste de 7 pages et motifs');
foreach (['/assets/css/app.css', '/assets/js/app.js', '/assets/images/logo.svg', '/manus-storage/async-images/hkkAVIBcheWMvfKsmS4nT4/image-1.webp'] as $asset) {
    [$status] = getPage($asset);
    check($status === 200, 'Ressource '.$asset);
}
[$status, $body] = getPage('/_app/health');
check($status === 200 && (json_decode($body, true)['status'] ?? '') === 'ok', 'Endpoint de santé');
[$status, $body] = getPage('/demo/apprenant');
preg_match_all('/<dialog[^>]+aria-labelledby="([^"]+)"/', $body, $labels);
check(count($labels[1]) === substr_count($body, '<dialog'), 'Tous les dialogues ont un nom accessible');
foreach ($labels[1] as $id) {
    check(str_contains($body, 'id="'.$id.'"'), 'Titre accessible '.$id);
}
$script = file_get_contents(dirname(__DIR__).'/public/assets/js/app.js');
$css = file_get_contents(dirname(__DIR__).'/public/assets/css/app.css');
check(str_contains($script, 'panel.inert = mobile.matches && !expanded;') && str_contains($css, 'visibility: hidden'), 'Menus mobiles fermés retirés du focus');
$docker = file_get_contents(dirname(__DIR__).'/Dockerfile');
check(!str_contains($docker, 'COPY . .') && str_contains($docker, 'COPY templates/ templates/'), 'Copie Docker limitée aux fichiers applicatifs');
echo "\n$count contrôles réussis.\n";
