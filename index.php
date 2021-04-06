<?php
declare(strict_types=1);
if (getenv('DEVMODE')) {
    ini_set('display_errors', "1");
    ini_set('opcache.enable', "0");
}

require_once dirname(__FILE__) . '/vendor/autoload.php';

use Pecee\SimpleRouter\SimpleRouter;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

$loader = new FilesystemLoader(dirname(__FILE__) . '/views');

SimpleRouter::get('/', function() use ($loader) {
    $twig = new Environment($loader);
    echo $twig->render('race_sheet.twig', []);
});

SimpleRouter::start();
