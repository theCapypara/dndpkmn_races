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
            if (count($pokedexEntry) < 2 || !$pokedexEntry[1]) {
                continue;
            }
            $fake = false;
            $_id = (string) $pokedexEntry[1];
            if (substr((string) $pokedexEntry[1], 0, 1) == 'F') {
                $fake = true;
                $pokedexEntry[1] = substr((string) $pokedexEntry[1], 1);
            }
            $dexParts = explode(';', (string) $pokedexEntry[1]);
            if (count($dexParts) < 2) {
                $dexParts[] = '';
            }
            if ((int) $dexParts[0] == 0) {
                continue;
            }
            $operations[] = ['updateOne' => [
                ['_id' => $_id],
                ['$set' => ['_id' => $_id, 'dex' => (int) $dexParts[0], 'mod' => $dexParts[1], 'name' => $pokedexEntry[0], 'fake' => $fake, 'finished' => $pokedexEntry[2]]],
                ['upsert' => true]
            ]];
        }
        $dexCollection = $this->database->pokedex;
        $dexCollection->deleteMany([]);
        $dexCollection->bulkWrite($operations);
    }

    public function getRace($pokemonName)
    {
        $sheetCollection = $this->database->sheets;
        return $sheetCollection->findOne([ '_id' => $pokemonName]);
    }

    public function listExistingIds()
    {
        $sheetCollection = $this->database->sheets;
        $result = [];
        foreach ($sheetCollection->find([], ['projection' => ['_id' => true]]) as $row) {
            $result[] = $row['_id'];
        }
        return $result;
    }

    public function listPokemonMapByDexId($fakemon=false)
    {
        $dexCollection = $this->database->pokedex;
        $vals = [];
        foreach($dexCollection->find(['fake' => $fakemon], ['sort' => ['dex' => 1, 'mod' => 1]]) as $r) {
            $vals[$r['_id']] = $r;
        }
        return $vals;
    }

    public function listPokemonMapByName($fakemon=false)
    {
        $dexCollection = $this->database->pokedex;
        $vals = [];
        foreach($dexCollection->find(['fake' => $fakemon]) as $r) {
            $vals[$r['name']] = $r;
        }
        return $vals;
    }

    public function getPokemonDexByName($pokemonName)
    {
        $dexCollection = $this->database->pokedex;
        return $dexCollection->findOne([ 'name' => $pokemonName]);
    }

    private $_getPokemonDexAllEntriesForId_Cache = [];
    public function getPokemonDexAllEntriesForId($id, $fakemon)
    {
        $c = ((string) $id) . '_' . ((string) $fakemon);
        if (!in_array($c, $this->_getPokemonDexAllEntriesForId_Cache)) {
            $dexCollection = $this->database->pokedex;
            $this->_getPokemonDexAllEntriesForId_Cache[$c] = $dexCollection->find(['dex' => $id, 'fake' => $fakemon], ['sort' => ['mod' => 1]]);
        }
        return $this->_getPokemonDexAllEntriesForId_Cache[$c];
    }
}
