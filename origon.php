<?php
/**
 * Oregon Trail - CLI (PHP)
 * Save as: oregon_trail_cli.php
 * Run: php oregon_trail_cli.php
 *
 * Single-file, turn-based CLI recreation inspired by The Oregon Trail.
 * Features: party members, resources, travel/hunt/rest/trade, random events,
 * save/load to JSON file.
 */

// --------- CONFIG ---------
const TOTAL_MILES = 2000;
const DAILY_TRAVEL_MIN = 10;
const DAILY_TRAVEL_MAX = 25;
const STARTING_FOOD = 600;
const STARTING_AMMO = 100;
const STARTING_CASH = 200;
const STARTING_SPARE_WHEELS = 2;
const STARTING_SPARE_AXLES = 1;
const STARTING_SPARE_TONGUES = 1;
const PARTY_SIZE = 4; // player + companions
const MAX_HEALTH = 100;
const FOOD_CONSUMPTION_PER_PERSON = 2;
const HUNT_FOOD_MIN = 20;
const HUNT_FOOD_MAX = 80;
const SAVE_FILE = 'ot_save.json';

// --------- UTILITIES ---------
function slowPrint($text, $delay = 0) {
    // small delay optional; set 0 for instant
    echo $text . PHP_EOL;
}

function prompt($msg = '> ') {
    echo $msg;
    $line = fgets(STDIN);
    if ($line === false) return '';
    return trim($line);
}

function choose($options, $default = null) {
    $optStr = implode('/', $options);
    $ans = strtolower(prompt("($optStr) "));
    if ($ans === '' && $default !== null) return $default;
    return $ans;
}

function randRange($a, $b) {
    return rand($a, $b);
}

// --------- CLASSES ---------
class Person {
    public $name;
    public $health;
    public $alive;

    public function __construct($name) {
        $this->name = $name;
        $this->health = MAX_HEALTH;
        $this->alive = true;
    }

    public function injure($amount) {
        if (!$this->alive) return;
        $this->health -= $amount;
        if ($this->health <= 0) $this->die();
    }

    public function heal($amount) {
        if (!$this->alive) return;
        $this->health = min(MAX_HEALTH, $this->health + $amount);
    }

    public function die() {
        $this->alive = false;
        $this->health = 0;
        slowPrint("{$this->name} has died.");
    }

    public function status() {
        if (!$this->alive) return "{$this->name}: dead";
        return "{$this->name}: {$this->health}/" . MAX_HEALTH . " hp";
    }
}

class Party {
    public $members = []; // array of Person

    public function __construct($names) {
        foreach ($names as $n) {
            $this->members[] = new Person($n);
        }
    }

    public function aliveMembers() {
        return array_values(array_filter($this->members, function($m){ return $m->alive; }));
    }

    public function allDead() {
        return count($this->aliveMembers()) === 0;
    }

    public function summary() {
        $lines = array_map(function($m){ return $m->status(); }, $this->members);
        return implode(PHP_EOL, $lines);
    }
}

class Wagon {
    public $milesTraveled = 0;
    public $food;
    public $ammo;
    public $cash;
    public $spareWheels;
    public $spareAxles;
    public $spareTongues;
    public $days = 0;

    public function __construct() {
        $this->food = STARTING_FOOD;
        $this->ammo = STARTING_AMMO;
        $this->cash = STARTING_CASH;
        $this->spareWheels = STARTING_SPARE_WHEELS;
        $this->spareAxles = STARTING_SPARE_AXLES;
        $this->spareTongues = STARTING_SPARE_TONGUES;
    }

    public function consumeFood($party) {
        $alive = count($party->aliveMembers());
        $required = $alive * FOOD_CONSUMPTION_PER_PERSON;
        if ($this->food >= $required) {
            $this->food -= $required;
            return true;
        } else {
            $deficit = $required - max(0, $this->food);
            $this->food = 0;
            // distribute penalty randomly
            $aliveList = $party->aliveMembers();
            for ($i=0;$i<$deficit;$i++) {
                if (count($aliveList) === 0) break;
                $victim = $aliveList[array_rand($aliveList)];
                $victim->injure(3);
            }
            return false;
        }
    }

    public function status() {
        return "Miles: {$this->milesTraveled}/" . TOTAL_MILES . "  Days: {$this->days}\n"
             . "Food: {$this->food}  Ammo: {$this->ammo}  Cash: \${$this->cash}\n"
             . "Spare wheels: {$this->spareWheels}  spare axles: {$this->spareAxles}  spare tongues: {$this->spareTongues}";
    }
}

// --------- EVENTS ---------
function eventSickness($party, $wagon) {
    $alive = $party->aliveMembers();
    if (count($alive) === 0) return;
    $person = $alive[array_rand($alive)];
    $illness = ["typhoid","cholera","fever","dysentery"][rand(0,3)];
    slowPrint("{$person->name} fell ill with {$illness}.");
    $severity = randRange(10,40);
    $person->injure($severity);
    if ($person->alive) slowPrint("{$person->name} lost {$severity} hp.");
}

function eventWagonBreak($party, $wagon) {
    $parts = ['wheel','axle','tongue'];
    $part = $parts[array_rand($parts)];
    slowPrint("Your wagon broke: a {$part} is damaged.");
    if ($part == 'wheel') {
        if ($wagon->spareWheels > 0) {
            $wagon->spareWheels--;
            slowPrint("You used a spare wheel to repair it.");
        } else {
            slowPrint("No spare wheels! Repairs take time.");
            $wagon->days += 1;
            foreach ($party->aliveMembers() as $p) {
                if (rand(0,100) < 20) $p->injure(rand(1,8));
            }
        }
    } elseif ($part == 'axle') {
        if ($wagon->spareAxles > 0) {
            $wagon->spareAxles--;
            slowPrint("You used a spare axle.");
        } else {
            slowPrint("No spare axles. You lose time and food.");
            $wagon->days += 2;
            $lost = min($wagon->food, 20);
            $wagon->food -= $lost;
            slowPrint("You lost {$lost} food while stranded.");
        }
    } else { // tongue
        if ($wagon->spareTongues > 0) {
            $wagon->spareTongues--;
            slowPrint("You replaced the tongue from spares.");
        } else {
            slowPrint("No spares. Slow repairs required.");
            $wagon->days += 1;
        }
    }
}

function eventRiverCrossing($party, $wagon) {
    $width = randRange(50,400);
    slowPrint("You reach a river {$width}ft wide. Options: ford, float, ferry.");
    $choice = strtolower(prompt("(ford/float/ferry) "));
    if ($choice === '' ) $choice = 'ford';
    if ($choice === 'ford') {
        $risk = min(0.6, 1 - ($wagon->food / 1000.0));
        if (rand(0,100)/100.0 < $risk) {
            slowPrint("The wagon hits a hidden current. Water floods the wagon!");
            $lost = min($wagon->food, randRange(20,80));
            $wagon->food -= $lost;
            slowPrint("You lost {$lost} food to the river.");
            if (rand(0,100) < 20) {
                $alive = $party->aliveMembers();
                if (count($alive)>0) $alive[array_rand($alive)]->die();
            }
        } else {
            slowPrint("You cross safely but it's tiring.");
            foreach ($party->aliveMembers() as $p) {
                if (rand(0,100) < 12) $p->injure(randRange(0,6));
            }
        }
    } elseif ($choice === 'float') {
        $cost = randRange(10,30);
        if ($wagon->cash >= $cost) {
            $wagon->cash -= $cost;
            slowPrint("You hire a raft/ferry and pay \${$cost}. Crossed safely.");
        } else {
            slowPrint("Can't afford a ferry. You try to float and struggle.");
            if (rand(0,100) < 50) {
                $alive = $party->aliveMembers();
                if (count($alive)>0) $alive[array_rand($alive)]->injure(randRange(5,30));
            }
        }
    } else { // ferry
        $cost = randRange(20,60);
        if ($wagon->cash >= $cost) {
            $wagon->cash -= $cost;
            slowPrint("You pay \${$cost} for the ferry; safe and quick.");
        } else {
            slowPrint("No ferry you can afford. You must ford.");
            eventRiverCrossing($party, $wagon);
        }
    }
}

function eventThieves($party, $wagon) {
    $lostCash = min($wagon->cash, randRange(10,60));
    $lostFood = min($wagon->food, randRange(10,100));
    $wagon->cash -= $lostCash;
    $wagon->food -= $lostFood;
    slowPrint("Thieves stole \${$lostCash} and {$lostFood} food during the night.");
}

function eventBounty($party, $wagon) {
    $gain = randRange(10,60);
    $wagon->cash += $gain;
    slowPrint("You found a hidden stash: +\${$gain}.");
}

function randomEvent($party, $wagon) {
    $r = rand(0,100)/100.0;
    if ($r < 0.08) eventSickness($party,$wagon);
    elseif ($r < 0.14) eventWagonBreak($party,$wagon);
    elseif ($r < 0.18) eventRiverCrossing($party,$wagon);
    elseif ($r < 0.22) eventThieves($party,$wagon);
    elseif ($r < 0.26) eventBounty($party,$wagon);
    // else nothing
}

// --------- ACTIONS ---------
function actionTravel($party, $wagon) {
    slowPrint("You decide to travel today.");
    $distance = randRange(DAILY_TRAVEL_MIN, DAILY_TRAVEL_MAX);
    $wagon->milesTraveled += $distance;
    $wagon->days += 1;
    slowPrint("You travel {$distance} miles.");
    $wagon->consumeFood($party);
    randomEvent($party,$wagon);
    foreach ($party->aliveMembers() as $p) {
        if (rand(0,100) < 12) $p->injure(randRange(0,6));
    }
}

function actionRest($party, $wagon) {
    slowPrint("You rest for a day, tending to the party.");
    $wagon->days += 1;
    $wagon->consumeFood($party);
    foreach ($party->aliveMembers() as $p) $p->heal(randRange(8,20));
    if (rand(0,100) < 10) slowPrint("Rest helped; morale and health improved.");
}

function actionHunt($party, $wagon) {
    slowPrint("You go hunting.");
    $wagon->days += 1;
    if ($wagon->ammo <= 0) {
        slowPrint("No ammo to hunt with.");
        return;
    }
    $shots = randRange(5,20);
    $used = min($wagon->ammo, $shots);
    $wagon->ammo -= $used;
    $successChance = 0.4 + ($used / 100.0);
    if (rand(0,100)/100.0 < $successChance) {
        $foodGained = randRange(HUNT_FOOD_MIN, HUNT_FOOD_MAX);
        $wagon->food += $foodGained;
        slowPrint("Success! You brought back {$foodGained} food (used {$used} ammo).");
    } else {
        slowPrint("You returned with little. (used {$used} ammo)");
    }
    if (rand(0,100) < 5) {
        $alive = $party->aliveMembers();
        if (count($alive) > 0) $alive[array_rand($alive)]->injure(randRange(2,18));
    }
}

function actionTrade($party, $wagon) {
    slowPrint("You find a trading post / fort.");
    $wagon->days += 1;
    $priceFood = randRange(5,15);
    $priceAmmo = randRange(2,5);
    $priceWheel = randRange(30,80);
    slowPrint("Prices: food {$priceFood}/unit, ammo {$priceAmmo}/round, wheel \${$priceWheel}.");
    while (true) {
        slowPrint("Options: buy_food, buy_ammo, buy_wheel, leave");
        $c = strtolower(prompt("(buy_food/buy_ammo/buy_wheel/leave) "));
        if (strpos($c,'buy_food') === 0) {
            $qty = intval(prompt("How many food units? "));
            $cost = $qty * $priceFood;
            if ($cost <= $wagon->cash) {
                $wagon->cash -= $cost;
                $wagon->food += $qty;
                slowPrint("Bought {$qty} food for \${$cost}.");
            } else slowPrint("Not enough money.");
        } elseif (strpos($c,'buy_ammo') === 0) {
            $qty = intval(prompt("How many ammo rounds? "));
            $cost = $qty * $priceAmmo;
            if ($cost <= $wagon->cash) {
                $wagon->cash -= $cost;
                $wagon->ammo += $qty;
                slowPrint("Bought {$qty} ammo for \${$cost}.");
            } else slowPrint("Not enough money.");
        } elseif (strpos($c,'buy_wheel') === 0) {
            if ($wagon->cash >= $priceWheel) {
                $wagon->cash -= $priceWheel;
                $wagon->spareWheels += 1;
                slowPrint("Bought a spare wheel for \${$priceWheel}.");
            } else slowPrint("Not enough money.");
        } else break;
    }
}

// --------- SAVE / LOAD ---------
function saveGame($party, $wagon, $filename = SAVE_FILE) {
    $data = [
        'milesTraveled' => $wagon->milesTraveled,
        'days' => $wagon->days,
        'food' => $wagon->food,
        'ammo' => $wagon->ammo,
        'cash' => $wagon->cash,
        'spareWheels' => $wagon->spareWheels,
        'spareAxles' => $wagon->spareAxles,
        'spareTongues' => $wagon->spareTongues,
        'party' => array_map(function($p){ return ['name'=>$p->name,'health'=>$p->health,'alive'=>$p->alive]; }, $party->members)
    ];
    file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT));
    slowPrint("Game saved to {$filename}.");
}

function loadGame($filename = SAVE_FILE) {
    if (!file_exists($filename)) return null;
    $raw = file_get_contents($filename);
    $data = json_decode($raw, true);
    if (!$data) return null;
    $wagon = new Wagon();
    $wagon->milesTraveled = $data['milesTraveled'] ?? 0;
    $wagon->days = $data['days'] ?? 0;
    $wagon->food = $data['food'] ?? STARTING_FOOD;
    $wagon->ammo = $data['ammo'] ?? STARTING_AMMO;
    $wagon->cash = $data['cash'] ?? STARTING_CASH;
    $wagon->spareWheels = $data['spareWheels'] ?? STARTING_SPARE_WHEELS;
    $wagon->spareAxles = $data['spareAxles'] ?? STARTING_SPARE_AXLES;
    $wagon->spareTongues = $data['spareTongues'] ?? STARTING_SPARE_TONGUES;
    $names = array_map(function($p){ return $p['name']; }, $data['party']);
    $party = new Party($names);
    // restore health/alive
    foreach ($party->members as $i => $member) {
        if (isset($data['party'][$i])) {
            $member->health = $data['party'][$i]['health'];
            $member->alive = $data['party'][$i]['alive'];
        }
    }
    return ['party'=>$party,'wagon'=>$wagon];
}

// --------- WIN/LOSS CHECKS ---------
function checkVictory($party, $wagon) {
    if ($wagon->milesTraveled >= TOTAL_MILES) {
        slowPrint("\n=== YOU MADE IT TO OREGON! ===\n");
        slowPrint("Days: {$wagon->days}  Remaining cash: \${$wagon->cash}  Food left: {$wagon->food}");
        $alive = count($party->aliveMembers());
        slowPrint("Survivors: {$alive}/" . PARTY_SIZE);
        return true;
    }
    return false;
}

function checkFailure($party, $wagon) {
    if ($party->allDead()) {
        slowPrint("All party members have died. The journey ends.");
        return true;
    }
    if ($wagon->food <= 0 && $wagon->ammo <= 0 && $wagon->cash <= 0) {
        $alive = $party->aliveMembers();
        $poor = true;
        foreach ($alive as $p) if ($p->health >= 30) $poor = false;
        if ($poor) {
            slowPrint("You have run out of supplies and hope. The journey cannot continue.");
            return true;
        }
    }
    return false;
}

// --------- BOOT / MAIN LOOP ---------
function main() {
    slowPrint("=== Oregon Trail (PHP CLI) ===");
    $load = strtolower(prompt("Load existing save? (y/n) "));
    if ($load === 'y') {
        $res = loadGame();
        if ($res === null) {
            slowPrint("No valid save found. Starting new game.");
            $names = getNames();
            $party = new Party($names);
            $wagon = new Wagon();
        } else {
            $party = $res['party'];
            $wagon = $res['wagon'];
            slowPrint("Save loaded.");
        }
    } else {
        $names = getNames();
        $party = new Party($names);
        $wagon = new Wagon();
    }

    while (true) {
        slowPrint("\n--- STATUS ---");
        slowPrint($wagon->status());
        slowPrint("\nParty:");
        slowPrint($party->summary());
        if (checkVictory($party,$wagon) || checkFailure($party,$wagon)) break;

        slowPrint("\nActions: travel, rest, hunt, trade, status, save, quit");
        $action = strtolower(prompt("Choose action: "));
        if ($action === 'travel') actionTravel($party,$wagon);
        elseif ($action === 'rest') actionRest($party,$wagon);
        elseif ($action === 'hunt') actionHunt($party,$wagon);
        elseif ($action === 'trade') actionTrade($party,$wagon);
        elseif ($action === 'status') {
            slowPrint($wagon->status());
            slowPrint($party->summary());
        } elseif ($action === 'save') saveGame($party,$wagon);
        elseif ($action === 'quit') {
            $q = strtolower(prompt("Save before quitting? (y/n) "));
            if ($q === 'y') saveGame($party,$wagon);
            slowPrint("Quitting. Goodbye.");
            break;
        } else {
            slowPrint("Unknown action. Try again.");
        }
    }
}

function getNames() {
    slowPrint("Name your party (you + " . (PARTY_SIZE-1) ." companions).");
    $names = [];
    for ($i=1;$i<=PARTY_SIZE;$i++) {
        $n = prompt("Name #{$i}: ");
        if ($n === '') $n = "Traveler{$i}";
        $names[] = $n;
    }
    return $names;
}

main();
