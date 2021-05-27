<?php
declare(strict_types=1);

require_once dirname(__FILE__) . '/config.php';
if (Config::isDevmode()) {
    ini_set('display_errors', "1");
    ini_set('opcache.enable', "0");
} else {
    ini_set('display_errors', "0");
}

require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/vendor/pecee/simple-router/helpers.php';
require_once dirname(__FILE__) . '/db.php';
require_once dirname(__FILE__) . '/content.php';
require_once dirname(__FILE__) . '/race_index.php';
require_once dirname(__FILE__) . '/twig_util.php';

use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

$loader = new FilesystemLoader(dirname(__FILE__) . '/views');

function createDb()
{
    return new Db(
        Config::getDbUri(), Config::getDbName()
    );
}

function createHeadData($canonical, $subPageTitle=null)
{
    $siteName = 'Pokémon D&D - Race Sheets';
    if ($subPageTitle) {
        $title = $subPageTitle . ' - ' . $siteName;
    } else {
        $subPageTitle = $siteName;
        $title = $siteName;
    }
    return [
        'title' => $title,
        'description' => null,
        'og_title' => $subPageTitle,
        'og_site_name' => $siteName,
        'canonical' => $canonical
    ];
}

SimpleRouter::get('/', function() use ($loader) {
    $twig = new Environment($loader);
    $function = new TwigFunction('numberToRoman', function (...$args) {
        return numberToRoman(...$args);
    });
    $twig->addFunction($function);
    echo $twig->render('index.twig', [
        'index' => (new RaceIndex(createDb()))->createFull(),
        'head' => createHeadData(''),
        'showOg' => true
    ]);
});

SimpleRouter::post('/submitSHEET', function() {
    if (request()->getHeader('X-PMDND-Authorization', '') != Config::getAuthToken()) {
        echo json_encode([
            'status' => 'error',
            'message' => request()->getHeader('X-PMDND-Authorization', '')
        ]);
        //response()->httpCode(401);
        return;
    }
    try {
        if (request()->getContentType() != 'application/json') {
            throw new Exception("Invalid / no data.");
        }
        $sheets = @json_decode(file_get_contents('php://input'), true);
        if ($sheets === null || !is_array($sheets)) {
            throw new Exception("Invalid / no data.");
        }
        createDb()->import($sheets);
        echo json_encode([
            'status' => 'ok'
        ]);
    } catch (Exception $ex) {
        echo json_encode([
            'status' => 'error',
            'message' => $ex->getMessage()
        ]);
    }
});

SimpleRouter::post('/submitDEX', function() {
    if (request()->getHeader('X-PMDND-Authorization', '') != Config::getAuthToken()) {
        echo json_encode([
            'status' => 'error',
            'message' => request()->getHeader('X-PMDND-Authorization', '')
        ]);
        //response()->httpCode(401);
        return;
    }
    try {
        if (request()->getContentType() != 'application/json') {
            throw new Exception("Invalid / no data.");
        }
        $dex = @json_decode(file_get_contents('php://input'), true);
        if ($dex === null || !is_array($dex)) {
            throw new Exception("Invalid / no data.");
        }
        createDb()->importPokedex($dex);
        echo json_encode([
            'status' => 'ok'
        ]);
    } catch (Exception $ex) {
        echo json_encode([
            'status' => 'error',
            'message' => $ex->getMessage()
        ]);
    }
});

SimpleRouter::get('/{pokemon}', function($pokemonName) use ($loader) {
    $twig = new Environment($loader);
    $db = createDb();
    $race = $db->getRace($pokemonName);
    if (!$race) {
        response()->httpCode(404);
        echo $twig->render('not_found.twig', [
            'head' => createHeadData(''),
            'showOg' => false
        ]);
        return;
    }
    $pokedexRow = $db->getPokemonDexByName($race['name']);
    $pokePageNum = RaceIndex::getRacePageNum($pokedexRow, $db);

    $raceBefore = null;
    $raceNext = null;
    $allEntries = RaceIndex::listAll($db);
    $index = RaceIndex::index($allEntries, $pokedexRow);

    $icons = [];
    $fileList = glob(dirname(__FILE__) . '/assets/poke_mini/*');
    //Loop through the array that glob returned.
    foreach($fileList as $filename){
        $e = explode('/', $filename);
        $icons[] = end($e);
    }

    if ($index !== false && $index > 0) {
        $entryBefore = $allEntries[$index - 1];
        $r = $db->getRace(normalizeName($entryBefore['name']));
        $iconCandidate = ((string) $entryBefore['_id']) . '.png';
        $raceBefore = [
            '_id' => $r['_id'],
            'name' => $entryBefore['name'],
            'icon' => in_array($iconCandidate, $icons) ? $iconCandidate : null,
            'pagenum' => RaceIndex::getRacePageNum($entryBefore, $db)
        ];
    }
    if ($index !== false && $index < (count($allEntries) - 1)) {
        $entryNext = $allEntries[$index + 1];
        $r = $db->getRace(normalizeName($entryNext['name']));
        $iconCandidate = ((string) $entryNext['_id']) . '.png';
        $raceNext = [
            '_id' => $r['_id'],
            'name' => $entryNext['name'],
            'icon' => in_array($iconCandidate, $icons) ? $iconCandidate : null,
            'pagenum' => RaceIndex::getRacePageNum($entryNext, $db)
        ];
    }

    echo $twig->render('race_sheet.twig', [
        'pokemon' => $race,
        'head' => createHeadData($race['_id'], $race['name']),
        'showOg' => true,
        'content' => (new Content($twig, $race, $pokePageNum))->render(),
        'prev_pokemon' => $raceBefore,
        'next_pokemon' => $raceNext,
    ]);
});

SimpleRouter::error(function(Request $request, \Exception $exception) use ($loader) {
    if($exception instanceof NotFoundHttpException && $exception->getCode() === 404) {
        $twig = new Environment($loader);
        response()->httpCode(404);
        echo $twig->render('not_found.twig', [
            'head' => createHeadData(''),
            'showOg' => false
        ]);
    }

});

SimpleRouter::start();
