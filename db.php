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
            if (count($pokedexEntry) < 3 || !$pokedexEntry[1]) {
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
        return new ArrayObject(json_decode('{"_id": "alcremie", "_rowId":2,"name":"Alcremie","type1":"Fairy","type2":"","pic2_pos":"","artist":"Mirrin","artist_link":"https://thedorfmirrin.tumblr.com/","stage":2,"gen":8,"attacks_link":"https://docs.google.com/spreadsheets/d/1rppONBDugp74m8ZURtudxaeDc1XGvZyaA2FhmR_gjSI/edit#gid=1347918519","attacks":[{"name":"Sweet Kiss","url":"https://docs.google.com/document/d/1hdSP1x8hNecQVKzJL7Wem4yPTJGCB_CwDPIqhfNJ-JQ/edit"},{"name":"Recover","url":"https://docs.google.com/document/d/18_rcm-PRcmQ7HG4ZZlM1TN4WCtF54hq-WHyjqz2omLQ/edit"},{"name":"Aromatherapy","url":"https://docs.google.com/document/d/1_vNDOiT2TrvmkxS9NHBcxgOWl3U6-lWNU4E_1sASVuU/edit"}],"attacks_l7":null,"abilities":[{"name":"Sweet Veil","flavor":"You concentrate and start to let off a sweet smelling arora. As the smell reaches your drowzee friends, they snap awake. With a clear mind you all fall into formation, now it\'s your turn.","description":"As a reaction, if you or an ally within 20 feet of you is currently or begins suffering the effects of the sleep condition, you may remove that condition as a reaction.","mega":"","url":""},{"name":"Aroma Veil","flavor":"You raise your arms and a sweet smell envelops the area around you. You must keep your friends as protected as much you can. This veil may not protect against everything, but it should help, even if it is by just a little.","description":"You and all allies within 20 feet can not be targeted by the racial attacks Taunt, Torment, Encore, Disable, Cursed Body, Heal Block or Infatuation. Also, when you or any ally within 20 feet take Psychic damage, the target takes less damage equal to their proficiency modifier.","mega":"","url":""}],"height":"1’00”","weight":" 1 LBS","speed":"25 Feet","evotraits":[{"title":"Treats","description":"The treats that you use to evolve have different effects on you depending on what one you use. You may gain advantage when making checks using a specific modifier an amount of times equal to your proficiency bonus per long rest, the modifier is determined by your treat. Strawberry is Strength, Blueberry is Dexterity, Heart is Constitution, Star is Intelligence, Flower is Wisdom, Bow is Charisma, and Clover is any modifier however you can’t gain advantage with the same modifier more than once per long rest. When making skill checks involving the specific modifier, this trait can be used."},{"title":"Cream","description":"The type of flavor you evolve into gives you unique attributes. See the Alcremie supplement for more info regarding each variation. "}],"alignment":"Although Alcremie have different alignments based on the type of cream they evolve into, most are kind hearted Pokemon. They try to help others but are weary of their surroundings and may be too afraid of situations to act when they are needed.","age":"The Alcremie line live to be about 100 years old. They reach maturity around 17 but are not considered to be adults till 20. As Alcremie get older their colors become more vibrant and their attitude also tends to get stronger. ","languages":"You can speak, read and write Common and Raglish. The way most Alcremie speak is often determined by their form, however most creatures can agree that the words they speak are spoken with elegance. Their words seem to always carry certainty and flow nicely into one another.    ","heads":[{"title":"Ritual","description":"When Milcery evolve into Alcremie their cells shift in different ways depending on the ritual done to evolve and the item they use to make the transformation. Their attitude and way of acting will also change depending on the form they take. The sweets they use to evolve are extremely rare and are very hard to make and will give the Alcremie special traits upon evolving depending on the type of sweet. The recipe and process to make the sweets is a well kept secret only known by the most elite Alcremie."},{"title":"Sweat","description":"The sweat of Alcremie is a popular cooking ingredient as a little bit of it gives a powerful tasty punch, the flavor changing depending on the form of the Alcremie who made it. The sweat is curdled and mixed together to make a cooking ingredient called ‘sweet cream’ that has some magical properties. A number of great cooks will use this cream in their food to give it an extra kick and adventures may drink a cup of it before venturing out to help give them the edge on whatever comes their way for the day."}],"location":{"title":"Clans","description":"Alcremie will often live in towns or clans with similar creamed Alcremie where they become master cooks or great magicians known for their potions and desserts. It is said the best food in the world can only be made by an Alcremie. They often gather in clans of similar creamed Alcreamie and have cooking dance wars with other clans. They will spend the whole day cooking while they dance to help keep everything working together and mixed properly. They may toss the food up into the air, spin around and catch it, or sway their hips as they use a spoon to place toppings on just right, the other members of their clan playing the music as they cook. The sight is one to behold as wonderful smells and dances can be seen, and the winner is given the title of the best cooks for that year."},"stories":["“No! I’m perfect!” Alcremie shouted as she attempted to get up, tears streaming down her face. She was beaten, bruised and hurting, but there was one thing her foe could never take from her, her pride. She took a deep breath and glared at them through her tears, nothing they could do would ever be enough to destroy that. She tried to pull herself to her feet but they gave way under her. This creature didn’t care for her or anyone else, they simply wanted to strip her of all her natural beauty, but what they didn’t know was that one\'s beauty was not displayed on the outside, but on the inside. Her beauty was buried so deep that they would never be able to touch it. They could tear her apart or even kill her, but they would never be able to reach it.","\rStanding outside of the bakery Alcremie took a deep breath in and let its sweet aroma fill her lungs. She looked in the store windows at the perfect cakes, the wonderful pastries, this was home. She belonged in that store making this wonderful food, she just had to figure out how to get them to hire a well known thief."],"asi":"The sweet, flavorful smells that waft off you often help you convince others of things and your connection with sweets help you understand nature magic a bit better. Your Charisma and Wisdom score increase by +1. See rules on racial ability score increase for more information","evolution":"This is the final evolution of this line. Alcremie can no longer evolve.  ","names":"Upon evolution many Pokemon change their name to better fit their new form and their new lifestyle. Some possible new names a Alcremie might take on are:\nMawhip, Charmilly, Alcremie, Pokusan, Mawhiping, Seungnaaihsin or Milky.\n\t","type_advantages":"The below are the effectiveness of racial attacks against this pokemon based on type:\nNot Very effective: Fighting, Bug and Dark\nSuper Effective: Poison and Steel\nImmune: Dragon\nSee ‘type effectiveness’ for more information.","pokedex":869,"layout":"f1,h1,h2,loc,align,size,speed,age:trait1;bb,lang,natu,pic,asi,evo,names,abil,atks,effeness,trait2,f2","extra":[{"title":"Testing Simple","flavor":"","content":"<p>\nThis is simple content. <br> for line breaks!\nLorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Felis eget nunc lobortis mattis aliquam faucibus purus. Augue mauris augue neque gravida. Suscipit adipiscing bibendum est ultricies. Dui sapien eget mi proin. Egestas integer eget aliquet nibh praesent tristique magna sit amet. Donec et odio pellentesque diam volutpat commodo sed egestas. Felis imperdiet proin fermentum leo vel orci. Commodo quis imperdiet massa tincidunt nunc pulvinar. Ipsum suspendisse ultrices gravida dictum fusce ut. Velit ut tortor pretium viverra suspendisse potenti. Risus viverra adipiscing at in tellus integer feugiat scelerisque varius. Cum sociis natoque penatibus et magnis dis parturient montes nascetur.\n</p>\n<p>\nA new paragraph.\n</p>","image":"extra1.png"},{"title":"Testing Complex","flavor":"I have flavor.","content":"<p>\nThis is complex content.\nLorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Felis eget nunc lobortis mattis aliquam faucibus purus. Augue mauris augue neque gravida. Suscipit adipiscing bibendum est ultricies. Dui sapien eget mi proin. Egestas integer eget aliquet nibh praesent tristique magna sit amet. Donec et odio pellentesque diam volutpat commodo sed egestas. Felis imperdiet proin fermentum leo vel orci. Commodo quis imperdiet massa tincidunt nunc pulvinar. Ipsum suspendisse ultrices gravida dictum fusce ut. Velit ut tortor pretium viverra suspendisse potenti. Risus viverra adipiscing at in tellus integer feugiat scelerisque varius. Cum sociis natoque penatibus et magnis dis parturient montes nascetur.\n</p>\n<h2>\n    Racial Attacks: <span class=\"subheading\">Pick One</span>\n</h2>\n<p class=\"hanging book-entry\">\n    <span class=\"book-entry--title\"><a href=\"https://docs.google.com/document/d/1hdSP1x8hNecQVKzJL7Wem4yPTJGCB_CwDPIqhfNJ-JQ/edit\" target=\"_blank\">Sweet Kiss</a></span>\n        <span class=\"book-entry--page\">Pg XXXX</span>\n</p>\n<p class=\"hanging book-entry\">\n        <span class=\"book-entry--title\"><a href=\"https://docs.google.com/document/d/18_rcm-PRcmQ7HG4ZZlM1TN4WCtF54hq-WHyjqz2omLQ/edit\" target=\"_blank\">Recover</a></span>\n        <span class=\"book-entry--page\">Pg XXXX</span>\n</p>\n<p class=\"hanging book-entry\">\n        <span class=\"book-entry--title\"><a href=\"https://docs.google.com/document/d/1_vNDOiT2TrvmkxS9NHBcxgOWl3U6-lWNU4E_1sASVuU/edit\" target=\"_blank\">Aromatherapy</a></span>\n        <span class=\"book-entry--page\">Pg XXXX</span>\n</p>","image":"extra2.png"},{"title":"Testing No Image","flavor":"","content":"<p>\nLorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Felis eget nunc lobortis mattis aliquam faucibus purus. Augue mauris augue neque gravida. Suscipit adipiscing bibendum est ultricies. Dui sapien eget mi proin. Egestas integer eget aliquet nibh praesent tristique magna sit amet. Donec et odio pellentesque diam volutpat commodo sed egestas. Felis imperdiet proin fermentum leo vel orci. Commodo quis imperdiet massa tincidunt nunc pulvinar. Ipsum suspendisse ultrices gravida dictum fusce ut. Velit ut tortor pretium viverra suspendisse potenti. Risus viverra adipiscing at in tellus integer feugiat scelerisque varius. Cum sociis natoque penatibus et magnis dis parturient montes nascetur.\n\nPurus sit amet volutpat consequat mauris nunc. Sit amet consectetur adipiscing elit duis tristique sollicitudin nibh. Quam adipiscing vitae proin sagittis nisl. Diam sollicitudin tempor id eu nisl nunc mi ipsum. Ac feugiat sed lectus vestibulum. Elementum sagittis vitae et leo duis ut. Ac tortor vitae purus faucibus ornare suspendisse sed. Scelerisque in dictum non consectetur a erat nam. Magnis dis parturient montes nascetur ridiculus mus mauris vitae ultricies. Id volutpat lacus laoreet non curabitur. Aliquet enim tortor at auctor urna nunc id. Curabitur gravida arcu ac tortor dignissim convallis aenean et tortor. Non sodales neque sodales ut etiam sit. Augue eget arcu dictum varius duis. Quis imperdiet massa tincidunt nunc pulvinar sapien et. Egestas sed tempus urna et pharetra pharetra. Massa eget egestas purus viverra. Facilisis mauris sit amet massa vitae tortor condimentum. Vel elit scelerisque mauris pellentesque pulvinar pellentesque habitant.\n\nAmet dictum sit amet justo donec. Viverra ipsum nunc aliquet bibendum enim facilisis gravida. Rhoncus dolor purus non enim praesent elementum facilisis. Ut venenatis tellus in metus. Sagittis eu volutpat odio facilisis mauris sit amet. Duis ultricies lacus sed turpis tincidunt. Turpis egestas sed tempus urna et pharetra pharetra. Dignissim cras tincidunt lobortis feugiat vivamus. Mi in nulla posuere sollicitudin aliquam ultrices sagittis orci. Et netus et malesuada fames. Arcu dui vivamus arcu felis bibendum. Ac odio tempor orci dapibus ultrices in. Nunc non blandit massa enim. Fusce ut placerat orci nulla pellentesque dignissim. Ornare lectus sit amet est placerat. Ac auctor augue mauris augue. Porttitor leo a diam sollicitudin tempor id eu. Egestas diam in arcu cursus euismod.\n\nInteger vitae justo eget magna fermentum iaculis eu. Ut diam quam nulla porttitor massa id. Id aliquet risus feugiat in ante metus. Tortor posuere ac ut consequat semper viverra nam. Accumsan in nisl nisi scelerisque eu ultrices vitae auctor. Suspendisse faucibus interdum posuere lorem ipsum dolor. Fames ac turpis egestas integer eget aliquet nibh praesent tristique. Sapien eget mi proin sed. Pulvinar pellentesque habitant morbi tristique senectus et. Mi quis hendrerit dolor magna eget est lorem. Vitae proin sagittis nisl rhoncus mattis rhoncus urna neque. Tristique risus nec feugiat in fermentum. Scelerisque felis imperdiet proin fermentum leo vel orci porta.\n\nAc turpis egestas maecenas pharetra convallis posuere morbi. A cras semper auctor neque vitae tempus quam pellentesque nec. Scelerisque varius morbi enim nunc faucibus a pellentesque. Condimentum id venenatis a condimentum vitae sapien pellentesque habitant. Ornare massa eget egestas purus viverra accumsan in. Risus in hendrerit gravida rutrum quisque non. Viverra maecenas accumsan lacus vel. Rutrum tellus pellentesque eu tincidunt tortor aliquam. Et netus et malesuada fames ac turpis. Felis bibendum ut tristique et egestas. Quis viverra nibh cras pulvinar mattis nunc. A condimentum vitae sapien pellentesque habitant.\n\nViverra mauris in aliquam sem fringilla ut morbi tincidunt. Enim nunc faucibus a pellentesque sit. Nunc consequat interdum varius sit amet mattis vulputate. Sit amet risus nullam eget felis eget nunc lobortis. Et sollicitudin ac orci phasellus egestas tellus. Ipsum consequat nisl vel pretium lectus quam. Rhoncus est pellentesque elit ullamcorper dignissim cras tincidunt lobortis. Sit amet est placerat in egestas erat. Nec nam aliquam sem et tortor. Integer enim neque volutpat ac. Blandit cursus risus at ultrices mi. Eleifend donec pretium vulputate sapien nec sagittis. Sapien pellentesque habitant morbi tristique senectus et. In hac habitasse platea dictumst vestibulum rhoncus est pellentesque elit. Neque sodales ut etiam sit amet nisl purus in mollis.\n\nMi quis hendrerit dolor magna eget est lorem ipsum dolor. Tellus rutrum tellus pellentesque eu tincidunt tortor aliquam. Nisi quis eleifend quam adipiscing vitae proin. Tristique magna sit amet purus. Habitasse platea dictumst vestibulum rhoncus est pellentesque elit. Varius quam quisque id diam vel. Hac habitasse platea dictumst vestibulum rhoncus. Odio tempor orci dapibus ultrices in iaculis. Turpis massa tincidunt dui ut ornare lectus sit amet. Imperdiet massa tincidunt nunc pulvinar. Nibh venenatis cras sed felis eget velit. Lacus suspendisse faucibus interdum posuere lorem ipsum dolor sit. Maecenas volutpat blandit aliquam etiam. Magna eget est lorem ipsum. Diam vel quam elementum pulvinar etiam. Justo nec ultrices dui sapien eget mi proin sed libero. Morbi tin","image":""}]}', true));
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
