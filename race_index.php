<?php
declare(strict_types=1);

class RaceIndex
{
    const GENERATIONS = [
        [151, '1st Generation'],
        [251, '2nd Generation'],
        [386, '3rd Generation'],
        [493, '4th Generation'],
        [649, '5th Generation'],
        [721, '6th Generation'],
        [807, '7th Generation'],
        [898, '8th Generation'],
        [9999, '9th Generation'],
    ];

    const FAKEMON_LABEL = 'Fakemon';

    /**
     * @var Db
     */
    private $db;

    public function __construct(Db $db)
    {
        $this->db = $db;
    }

    public function createFull()
    {
        $generations = [];
        foreach (self::GENERATIONS as $gen) {
            $generations[] = ['name' => $gen[1], 'items' => []];
        }
        $fakemonGen = count($generations);
        $generations[] = ['name' => self::FAKEMON_LABEL, 'items' => []];
        $activeGeneration = 0;
        $existing = $this->db->listExistingIds();

        $icons = [];
        $fileList = glob(dirname(__FILE__) . '/assets/poke_mini/*');

        //Loop through the array that glob returned.
        foreach($fileList as $filename){
            $e = explode('/', $filename);
            $icons[] = end($e);
        }

        foreach ($this->db->listPokemonMapByDexId(false) as $entry) {
            while ($entry['dex'] > self::GENERATIONS[$activeGeneration][0]) {
                $activeGeneration++;
            }
            $id = normalizeName($entry['name']);
            $iconCandidate = ((string) $entry['_id']) . '.png';
            $generations[$activeGeneration]['items'][] = [
                'dex' => $entry['dex'],
                'page' => self::getRacePageNum($entry, $this->db),
                'icon' => in_array($iconCandidate, $icons) ? $iconCandidate : null,
                'name' => $entry['name'],
                'id' => $id,
                'main' => !$entry['mod'],
                'finished' => $this->getFinishedState($entry, $id, $existing)
            ];
        }
        foreach ($this->db->listPokemonMapByDexId(true) as $entry) {
            $id = normalizeName($entry['name']);
            $iconCandidate = ((string) $entry['_id']) . '.png';
            $generations[$fakemonGen]['items'][] = [
                'dex' => $entry['dex'],
                'page' => self::getRacePageNum($entry, $this->db),
                'icon' => in_array($iconCandidate, $icons) ? $iconCandidate : null,
                'name' => $entry['name'],
                'id' => $id,
                'main' => !$entry['mod'],
                'finished' => $this->getFinishedState($entry, $id, $existing)
            ];
        }
        return $generations;
    }

    public static function listAll(Db $db)
    {
        return array_merge(array_values($db->listPokemonMapByDexId(false)), array_values($db->listPokemonMapByDexId(true)));
    }

    public static function index(array $allEntries, $pokedexRow)
    {
        return array_search($pokedexRow, $allEntries);
    }

    public static function getRacePageNum($pokedexRow, Db $db) {
        $prefix = $pokedexRow['fake'] ? 'F' : 'R';
        $num = (string) $pokedexRow['dex'];
        if (!$pokedexRow['mod']) {
            $pageNum = 1;
        } else {
            $all = $db->getPokemonDexAllEntriesForId($pokedexRow['dex'], $pokedexRow['fake']);
            $pageNumBefore = 0;
            foreach ($all as $entry) {
                $pages = 2;
                $race = $db->getRace(normalizeName($entry['name']));
                if ($race && array_key_exists('extra', $race)) {
                    $pages += ceil(count($race['extra']) / 2);
                }
                if ($entry['mod'] == $pokedexRow['mod']) {
                    break;
                }
                $pageNumBefore += $pages;
            }
            // TODO: Breaks with more than 2 pages per Pokémon.
            $pageNum = $pageNumBefore + 1;
        }
        return $prefix . $num . '.' . $pageNum;
    }

    private function getFinishedState($entry, $id, $existing)
    {
        if (!in_array($id, $existing) || !array_key_exists('finished', $entry)) {
            return 0;
        }
        switch ($entry['finished']) {
            case "Exists":
                return 1;
            case "Fully Finished":
                return 2;
        }
        return 0;
    }
}
