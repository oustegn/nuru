<?php

// Routeur réservé au serveur de développement PHP. Apache utilise public/.htaccess.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$file = realpath(__DIR__.'/public'.$path);
$root = realpath(__DIR__.'/public').DIRECTORY_SEPARATOR;
if ($file !== false && str_starts_with($file, $root) && is_file($file) && !str_ends_with($file, '.php')) {
    return false;
}
require __DIR__.'/public/index.php';
