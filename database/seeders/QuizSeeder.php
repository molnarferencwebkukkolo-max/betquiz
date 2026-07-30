<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Question;
use App\Models\Option;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Database\Seeder;

class QuizSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Kategóriák biztonságos létrehozása
        $sport = Category::firstOrCreate(
            ['slug' => 'sport'],
            ['name' => ['hu' => 'Sport', 'en' => 'Sports'], 'icon' => 'fa-trophy', 'is_active' => true]
        );

        $movies = Category::firstOrCreate(
            ['slug' => 'filmek'],
            ['name' => ['hu' => 'Filmek', 'en' => 'Movies'], 'icon' => 'fa-film', 'is_active' => true]
        );

        $astro = Category::firstOrCreate(
            ['slug' => 'csillagaszat'],
            ['name' => ['hu' => 'Csillagászat', 'en' => 'Astronomy'], 'icon' => 'fa-star', 'is_active' => true]
        );

        $chem = Category::firstOrCreate(
            ['slug' => 'kemia'],
            ['name' => ['hu' => 'Kémia', 'en' => 'Chemistry'], 'icon' => 'fa-vial', 'is_active' => true]
        );

        $user = User::first();
        $userId = $user ? $user->id : null;

        // Töröljük a régi kérdéseket
        Question::query()->delete();

        // --- SPORT (10 kérdés) ---
        $this->createQ($sport->id, 'easy', 'Hány percig tart egy szabályos labdarúgó-mérkőzés?', 'How many minutes does a regular football match last?', [
            ['90 perc', '90 minutes', true],
            ['80 perc', '80 minutes', false],
            ['60 perc', '60 minutes', false],
            ['100 perc', '100 minutes', false],
        ], $userId);

        $this->createQ($sport->id, 'easy', 'Hány játékos van egyszerre a pályán egy kosárlabdacsapatból?', 'How many players per team are on the basketball court?', [
            ['5', '5', true],
            ['6', '6', false],
            ['7', '7', false],
            ['11', '11', false],
        ], $userId);

        $this->createQ($sport->id, 'easy', 'Milyen gyakran rendezik meg a nyári olimpiai játékokat?', 'How often are the Summer Olympic Games held?', [
            ['4 évente', 'Every 4 years', true],
            ['2 évente', 'Every 2 years', false],
            ['5 évente', 'Every 5 years', false],
            ['Minden évben', 'Every year', false],
        ], $userId);

        $this->createQ($sport->id, 'medium', 'Melyik ország nyerte a 2018-as labdarúgó-világbajnokságot?', 'Which country won the 2018 FIFA World Cup?', [
            ['Franciaország', 'France', true],
            ['Horvátország', 'Croatia', false],
            ['Brazília', 'Brazil', false],
            ['Németország', 'Germany', false],
        ], $userId);

        $this->createQ($sport->id, 'medium', 'Hány pontot ér egy touchdown az amerikai futballban?', 'How many points is a touchdown worth in American football?', [
            ['6 pont', '6 points', true],
            ['3 pont', '3 points', false],
            ['7 pont', '7 points', false],
            ['5 pont', '5 points', false],
        ], $userId);

        $this->createQ($sport->id, 'medium', 'Ki nyerte a legtöbb F1-es világbajnoki címet (holtversenyben)?', 'Who won the most F1 World Championships (tied)?', [
            ['Schumacher és Hamilton', 'Schumacher & Hamilton', true],
            ['Senna és Prost', 'Senna & Prost', false],
            ['Verstappen és Vettel', 'Verstappen & Vettel', false],
            ['Alonso és Lauda', 'Alonso & Lauda', false],
        ], $userId);

        $this->createQ($sport->id, 'medium', 'Melyik városban rendezték meg az első újkori olimpiát 1896-ban?', 'Which city hosted the first modern Olympics in 1896?', [
            ['Athén', 'Athens', true],
            ['Párizs', 'Paris', false],
            ['Róma', 'Rome', false],
            ['London', 'London', false],
        ], $userId);

        $this->createQ($sport->id, 'hard', 'Hány golyó van a pályán egy snooker mérkőzés kezdetén?', 'How many balls are on the table at the start of a snooker frame?', [
            ['22', '22', true],
            ['16', '16', false],
            ['20', '20', false],
            ['24', '24', false],
        ], $userId);

        $this->createQ($sport->id, 'hard', 'Melyik úszónő nyert 5 aranyérmet a 2016-os riói olimpián?', 'Which female swimmer won 5 gold medals at Rio 2016?', [
            ['Katie Ledecky', 'Katie Ledecky', true],
            ['Hosszú Katinka', 'Katinka Hosszú', false],
            ['Missy Franklin', 'Missy Franklin', false],
            ['Sarah Sjöström', 'Sarah Sjöström', false],
        ], $userId);

        $this->createQ($sport->id, 'hard', 'Milyen hosszú a hivatalos maratoni futótáv?', 'How long is an official marathon race?', [
            ['42,195 km', '42.195 km', true],
            ['40 km', '40 km', false],
            ['45,5 km', '45.5 km', false],
            ['41,8 km', '41.8 km', false],
        ], $userId);


        // --- FILMEK (10 kérdés) ---
        $this->createQ($movies->id, 'easy', 'Ki rendezte a Titanic és az Avatar című filmeket?', 'Who directed Titanic and Avatar?', [
            ['James Cameron', 'James Cameron', true],
            ['Steven Spielberg', 'Steven Spielberg', false],
            ['Christopher Nolan', 'Christopher Nolan', false],
            ['Ridley Scott', 'Ridley Scott', false],
        ], $userId);

        $this->createQ($movies->id, 'easy', 'Milyen fegyvert használnak a Jedi lovagok a Star Wars-ban?', 'What weapon do Jedi Knights use in Star Wars?', [
            ['Fénykard', 'Lightsaber', true],
            ['Lézerpisztoly', 'Blaster', false],
            ['Plazmapuska', 'Plasma rifle', false],
            ['Vibrációs kard', 'Vibroblade', false],
        ], $userId);

        $this->createQ($movies->id, 'easy', 'Melyik filmben hangzik el a híres mondat: "I\'ll be back"?', 'In which movie is the phrase "I\'ll be back" famous?', [
            ['Terminátor', 'The Terminator', true],
            ['Rambo', 'Rambo', false],
            ['Die Hard', 'Die Hard', false],
            ['Predator', 'Predator', false],
        ], $userId);

        $this->createQ($movies->id, 'medium', 'Hány Oscar-díjat nyert A Gyűrűk Ura: A király visszatér?', 'How many Oscars did The Lord of the Rings: The Return of the King win?', [
            ['11', '11', true],
            ['9', '9', false],
            ['13', '13', false],
            ['7', '7', false],
        ], $userId);

        $this->createQ($movies->id, 'medium', 'Melyik kiadó képregényein alapulnak a Bosszúállók filmek?', 'Which comic publisher created The Avengers?', [
            ['Marvel', 'Marvel', true],
            ['DC Comics', 'DC Comics', false],
            ['Dark Horse', 'Dark Horse', false],
            ['Image Comics', 'Image Comics', false],
        ], $userId);

        $this->createQ($movies->id, 'medium', 'Ki alakította Jokert A sötét lovag (2008) című filmben?', 'Who played Joker in The Dark Knight (2008)?', [
            ['Heath Ledger', 'Heath Ledger', true],
            ['Joaquin Phoenix', 'Joaquin Phoenix', false],
            ['Jack Nicholson', 'Jack Nicholson', false],
            ['Jared Leto', 'Jared Leto', false],
        ], $userId);

        $this->createQ($movies->id, 'medium', 'Mi a neve a főszereplőnek a Mátrix című filmben?', 'What is the main character\'s name in The Matrix?', [
            ['Neo', 'Neo', true],
            ['Morpheus', 'Morpheus', false],
            ['Cypher', 'Cypher', false],
            ['Trinity', 'Trinity', false],
        ], $userId);

        $this->createQ($movies->id, 'hard', 'Melyik évben mutatták be a Ponyvaregényt (Pulp Fiction)?', 'In which year was Pulp Fiction released?', [
            ['1994', '1994', true],
            ['1992', '1992', false],
            ['1996', '1996', false],
            ['1998', '1998', false],
        ], $userId);

        $this->createQ($movies->id, 'hard', 'Ki szerezte a Star Wars és a Harry Potter filmek zenéjét?', 'Who composed the music for Star Wars and Harry Potter?', [
            ['John Williams', 'John Williams', true],
            ['Hans Zimmer', 'Hans Zimmer', false],
            ['Ennio Morricone', 'Ennio Morricone', false],
            ['Howard Shore', 'Howard Shore', false],
        ], $userId);

        $this->createQ($movies->id, 'hard', 'Melyik volt az első egész estés Disney rajzfilm?', 'What was the first full-length animated Disney film?', [
            ['Hófehérke és a 7 törpe', 'Snow White and the 7 Dwarfs', true],
            ['Pinokkió', 'Pinocchio', false],
            ['Bambi', 'Bambi', false],
            ['Miki egér: A gőzhajó', 'Steamboat Willie', false],
        ], $userId);


        // --- CSILLAGÁSZAT (10 kérdés) ---
        $this->createQ($astro->id, 'easy', 'Melyik a Naprendszer legnagyobb bolygója?', 'Which is the largest planet in the Solar System?', [
            ['Jupiter', 'Jupiter', true],
            ['Szaturnusz', 'Saturn', false],
            ['Neptunusz', 'Neptune', false],
            ['Föld', 'Earth', false],
        ], $userId);

        $this->createQ($astro->id, 'easy', 'Melyik bolygót nevezik "Vörös Bolygónak"?', 'Which planet is known as the "Red Planet"?', [
            ['Mars', 'Mars', true],
            ['Vénusz', 'Venus', false],
            ['Merkúr', 'Mercury', false],
            ['Jupiter', 'Jupiter', false],
        ], $userId);

        $this->createQ($astro->id, 'easy', 'Melyik galaxisban található a Föld?', 'Which galaxy is Earth located in?', [
            ['Tejútrendszer', 'Milky Way', true],
            ['Androméda', 'Andromeda', false],
            ['Triangulum', 'Triangulum', false],
            ['Sombrero', 'Sombrero', false],
        ], $userId);

        $this->createQ($astro->id, 'medium', 'Körülbelül hány perc alatt ér be a Nap fénye a Földre?', 'Roughly how many minutes does light from the Sun take to reach Earth?', [
            ['8 perc', '8 minutes', true],
            ['1 perc', '1 minute', false],
            ['12 perc', '12 minutes', false],
            ['Azonnal', 'Instantly', false],
        ], $userId);

        $this->createQ($astro->id, 'medium', 'Melyik bolygónak van a leglátványosabb gyűrűrendszere?', 'Which planet has the most prominent ring system?', [
            ['Szaturnusz', 'Saturn', true],
            ['Uranusz', 'Uranus', false],
            ['Neptunusz', 'Neptune', false],
            ['Jupiter', 'Jupiter', false],
        ], $userId);

        $this->createQ($astro->id, 'medium', 'Mi volt az első emberes holdra szállás űrhajója?', 'What was the spacecraft of the first crewed Moon landing?', [
            ['Apollo 11', 'Apollo 11', true],
            ['Apollo 13', 'Apollo 13', false],
            ['Apollo 8', 'Apollo 8', false],
            ['Gemini 4', 'Gemini 4', false],
        ], $userId);

        $this->createQ($astro->id, 'hard', 'Mi a Földhöz legközelebbi csillagrendszer (a Nap után)?', 'What is the closest star system to Earth (after the Sun)?', [
            ['Alpha Centauri', 'Alpha Centauri', true],
            ['Barnard-csillag', 'Barnard\'s Star', false],
            ['Betelgeuse', 'Betelgeuse', false],
            ['Vega', 'Vega', false],
        ], $userId);

        $this->createQ($astro->id, 'hard', 'Mi az az égitest, amiből még a fény sem tud kiszökni?', 'What object has gravity so strong that even light cannot escape?', [
            ['Fekete lyuk', 'Black hole', true],
            ['Fehér törpe', 'White dwarf', false],
            ['Szupernova', 'Supernova', false],
            ['Pulzár', 'Pulsar', false],
        ], $userId);

        $this->createQ($astro->id, 'hard', 'Melyik bolygón található a leghíresebb vulkán, az Olympus Mons?', 'On which planet is Olympus Mons located?', [
            ['Mars', 'Mars', true],
            ['Vénusz', 'Venus', false],
            ['Merkúr', 'Mercury', false],
            ['Io', 'Io', false],
        ], $userId);

        $this->createQ($astro->id, 'hard', 'Melyik évben sorolták át a Plutót törpebolygóvá?', 'In which year was Pluto reclassified as a dwarf planet?', [
            ['2006', '2006', true],
            ['2000', '2000', false],
            ['2010', '2010', false],
            ['1998', '1998', false],
        ], $userId);


        // --- KÉMIA (10 kérdés) ---
        $this->createQ($chem->id, 'easy', 'Mi a víz kémiai vegyjele?', 'What is the chemical formula of water?', [
            ['H2O', 'H2O', true],
            ['CO2', 'CO2', false],
            ['NaCl', 'NaCl', false],
            ['O2', 'O2', false],
        ], $userId);

        $this->createQ($chem->id, 'easy', 'Milyen vegyjele van az Aranynak?', 'What is the chemical symbol for Gold?', [
            ['Au', 'Au', true],
            ['Ag', 'Ag', false],
            ['Fe', 'Fe', false],
            ['Ar', 'Ar', false],
        ], $userId);

        $this->createQ($chem->id, 'easy', 'Milyen gáz teszi ki a légkör legnagyobb részét (~78%)?', 'Which gas makes up most of Earth\'s atmosphere (~78%)?', [
            ['Nitrogén', 'Nitrogen', true],
            ['Oxigén', 'Oxygen', false],
            ['Szén-dioxid', 'Carbon dioxide', false],
            ['Argon', 'Argon', false],
        ], $userId);

        $this->createQ($chem->id, 'medium', 'Hányas pH érték számít semlegesnek?', 'Which pH level is considered neutral?', [
            ['7', '7', true],
            ['0', '0', false],
            ['14', '14', false],
            ['5', '5', false],
        ], $userId);

        $this->createQ($chem->id, 'medium', 'Mi a konyhasó kémiai neve?', 'What is the chemical name for table salt?', [
            ['Nátrium-klorid', 'Sodium chloride', true],
            ['Kálium-karbonát', 'Potassium carbonate', false],
            ['Kalcium-szulfát', 'Calcium sulfate', false],
            ['Nátrium-hidroxid', 'Sodium hydroxide', false],
        ], $userId);

        $this->createQ($chem->id, 'medium', 'Melyik a legkönnyebb elem a periódusos rendszerben?', 'What is the lightest element in the periodic table?', [
            ['Hidrogén', 'Hydrogen', true],
            ['Hélium', 'Helium', false],
            ['Lítium', 'Lithium', false],
            ['Szén', 'Carbon', false],
        ], $userId);

        $this->createQ($chem->id, 'medium', 'Milyen elem alkotja a gyémántot és a grafitot is?', 'Which element forms both diamond and graphite?', [
            ['Szén', 'Carbon', true],
            ['Szilícium', 'Silicon', false],
            ['Foszfor', 'Phosphorus', false],
            ['Kén', 'Sulfur', false],
        ], $userId);

        $this->createQ($chem->id, 'hard', 'Melyik fémes elem folyékony szobahőmérsékleten?', 'Which metallic element is liquid at room temperature?', [
            ['Higany', 'Mercury', true],
            ['Ólom', 'Lead', false],
            ['Bróm', 'Bromine', false],
            ['Gallium', 'Gallium', false],
        ], $userId);

        $this->createQ($chem->id, 'hard', 'Milyen gáz szabadul fel savak és fémek reakciójakor?', 'Which gas is released during the reaction of acids and metals?', [
            ['Hidrogén', 'Hydrogen', true],
            ['Oxigén', 'Oxygen', false],
            ['Klór', 'Chlorine', false],
            ['Nitrogén', 'Nitrogen', false],
        ], $userId);

        $this->createQ($chem->id, 'hard', 'Ki alkotta meg a periódusos rendszert 1869-ben?', 'Who created the periodic table in 1869?', [
            ['Mengyelejev', 'Mendeleev', true],
            ['Lavoisier', 'Lavoisier', false],
            ['Marie Curie', 'Marie Curie', false],
            ['John Dalton', 'John Dalton', false],
        ], $userId);
    }

    /**
     * Pici segédfüggvény a tiszta és átlátható kódért.
     */
    private function createQ($categoryId, $diff, $huQ, $enQ, array $options, $quizOwnerId)
    {
        $quizId = Quiz::query()
            ->where('creator_id', $quizOwnerId)
            ->value('id');

        $q = Question::create([
            'quiz_id' => $quizId,
            'category_id' => $categoryId,
            'difficulty' => $diff,
            'question_text' => ['hu' => $huQ, 'en' => $enQ],
            'is_approved' => true,
            'is_active' => true,
        ]);

        foreach ($options as $opt) {
            Option::create([
                'question_id' => $q->id,
                'option_text' => ['hu' => $opt[0], 'en' => $opt[1]],
                'is_correct' => $opt[2]
            ]);
        }
    }
}
