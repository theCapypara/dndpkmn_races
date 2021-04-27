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

use Pecee\Http\Request;
use Pecee\SimpleRouter\Exceptions\NotFoundHttpException;
use Pecee\SimpleRouter\SimpleRouter;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

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

SimpleRouter::get('/', function() {
    redirect("https://dndpkmn.com/players-pokedex.html", 301);
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

SimpleRouter::get('/gens/{gen}', function($gen) use ($loader) {
    $twig = new Environment($loader);
    // TODO
});

SimpleRouter::get('/{pokemon}', function($pokemonName) use ($loader) {
    $twig = new Environment($loader);
    $race = createDb()->getRace($pokemonName);
    if (!$race) {
        response()->httpCode(404);
        echo $twig->render('not_found.twig', [
            'head' => createHeadData(''),
            'showOg' => false
        ]);
        return;
    }
    //var_dump($race);die;
    echo $twig->render('race_sheet.twig', [
        'pokemon' => $race,
        'head' => createHeadData($race['_id'], $race['name']),
        'showOg' => true,
        'pic1_exists' => file_exists(dirname(__FILE__) . '/assets/poke/'. $race['_id'] .'/pic1.png')
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
        return;
    }

});

SimpleRouter::start();
