<?php
declare(strict_types=1);

use MongoDB\Client;
use MongoDB\Database;

class Db
{
    /**
     * @var Client
     */
    private $client;


    /**
     * @var Database
     */
    private $database;

    function __construct($uri, $databaseName)
    {
        $this->client = new Client($uri);
        $this->database = $this->client->{$databaseName};
    }

    public function import(array $sheets)
    {
        $operations = [];
        foreach ($sheets as $sheet) {
            $operations[] = ['updateOne' => [
                ['_id' => normalizeName($sheet['name'])],
                ['$set' => $sheet],
                ['upsert' => true]
            ]];

        }
        $sheetCollection = $this->database->sheets;
        $sheetCollection->bulkWrite($operations);
    }

    public function importPokedex(array $pokedex)
    {
        $operations = [];
        foreach ($pokedex as $pokedexEntry) {
            $operations[] = ['updateOne' => [
                ['_id' => $pokedexEntry[1]],
                ['$set' => ['_id' => $pokedexEntry[1], 'name' => $pokedexEntry[0]]],
                ['upsert' => true]
            ]];
        }
        $dexCollection = $this->database->pokedex;
        $dexCollection->bulkWrite($operations);
    }

    public function getRace($pokemonName)
    {
        $sheetCollection = $this->database->sheets;
        return $sheetCollection->findOne([ '_id' => $pokemonName]);
    }

    public function listPokemonMapByDexId()
    {
        $dexCollection = $this->database->pokedex;
        $vals = [];
        foreach($dexCollection->find([], ['sort' => ['_id' => 1]]) as $r) {
            $vals[$r['_id']] = $r['name'];
        }
        return $vals;
    }

    public function listPokemonMapByName()
    {
        $dexCollection = $this->database->pokedex;
        $vals = [];
        foreach($dexCollection->find([]) as $r) {
            $vals[$r['name']] = $r['_id'];
        }
        return $vals;
    }

    public function getPokemonNameByDexId($dexId)
    {
        $dexCollection = $this->database->pokedex;
        $x = $dexCollection->findOne([ '_id' => $dexId]);
        return $x ? $x['name'] : null;
    }

    public function getPokemonDexIdByName($pokemonName)
    {
        $dexCollection = $this->database->pokedex;
        $x =  $dexCollection->findOne([ 'name' => $pokemonName]);
        return $x ? $x['_id'] : null;
    }
}
