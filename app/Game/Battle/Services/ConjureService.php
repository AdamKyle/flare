<?php

namespace App\Game\Battle\Services;

use App\Game\Maps\Events\UpdateMapDetailsBroadcast;
use App\Game\Maps\Services\MovementService;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use App\Flare\Models\CelestialFight;
use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\Monster;
use App\Flare\Models\Npc;
use App\Flare\Transformers\CharacterSheetTransformer;
use App\Flare\Transformers\KingdomTransformer;
use App\Flare\Values\NpcTypes;
use App\Game\Battle\Values\CelestialConjureType;
use App\Game\Core\Events\UpdateTopBarBroadcastEvent;
use App\Game\Kingdoms\Events\UpdateKingdom;
use App\Game\Messages\Builders\NpcServerMessageBuilder;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Flare\Cache\CoordinatesCache;


class ConjureService {

    const DAMAGE_KD_CHECK = 1000000000;

    private $manager;

    private $kingdomTransformer;

    private $characterTransformer;

    private $npcServerMessageBuilder;

    public function __construct(Manager $manager, KingdomTransformer $kingdom, CharacterSheetTransformer $characterSheetTransformer, NpcServerMessageBuilder $npcServerMessageBuilder) {
        $this->manager                 = $manager;
        $this->kingdomTransformer      = $kingdom;
        $this->characterTransformer    = $characterSheetTransformer;
        $this->npcServerMessageBuilder = $npcServerMessageBuilder;
    }

    public function movementConjure(Character $character) {

        if (CelestialFight::where('type', CelestialConjureType::PUBLIC)->get()->isNotEmpty()) {
            return;
        }

        $x = $this->getXPosition();
        $y = $this->getYPosition();

        $kingdom = $this->isAtKingdom($x, $y);
        $damagedKingdom = false;

        if (!is_null($kingdom)) {
            $damagedKingdom = $this->canDamageKingdom();
        }

        $monster = Monster::where('is_celestial_entity', true)->inRandomOrder()->first();

        $healthRange          = explode('-', $monster->health_range);
        $currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;

        CelestialFight::create([
            'monster_id'      => $monster->id,
            'character_id'    => null,
            'conjured_at'     => now(),
            'x_position'      => $x,
            'y_position'      => $y,
            'damaged_kingdom' => $damagedKingdom,
            'stole_treasury'  => $damagedKingdom,
            'weakened_morale' => $damagedKingdom,
            'current_health'  => $currentMonsterHealth,
            'max_health'      => $currentMonsterHealth,
            'type'            => CelestialConjureType::PUBLIC,
        ]);

        $plane = $monster->gameMap->name;

        $types = ['has awoken', 'has angered', 'has enraged', 'has set free', 'has set loose'];
        $randomIndex = rand(0, count($types) - 1);

        event(new GlobalMessageEvent($character->name . ' ' . $types[$randomIndex] . ': ' . $monster->name . ' on the ' . $plane . ' plane at (X/Y): ' . $x . '/' . $y));

        if ($damagedKingdom) {
            $this->damageKingdom($kingdom, $character, $this->getDamageAmount());
        }
    }

    public function conjure(Monster $monster, Character $character, string $type) {
        $x = $this->getXPosition();
        $y = $this->getYPosition();

        $kingdom = $this->isAtKingdom($x, $y);
        $damagedKingdom = false;

        if (!is_null($kingdom)) {
            $damagedKingdom = $this->canDamageKingdom();
        }

        $healthRange          = explode('-', $monster->health_range);
        $currentMonsterHealth = rand($healthRange[0], $healthRange[1]) + 10;

        $celestialFight = CelestialFight::create([
            'monster_id'      => $monster->id,
            'character_id'    => $character->id,
            'conjured_at'     => now(),
            'x_position'      => $x,
            'y_position'      => $y,
            'damaged_kingdom' => $damagedKingdom,
            'stole_treasury'  => $damagedKingdom,
            'weakened_morale' => $damagedKingdom,
            'current_health'  => $currentMonsterHealth,
            'max_health'      => $currentMonsterHealth,
            'type'            => $type === 'private'? CelestialConjureType::PRIVATE : CelestialConjureType::PUBLIC,
        ]);

        $plane = $character->map->gameMap->name;
        $type  = new CelestialConjureType($type === 'private'? CelestialConjureType::PRIVATE : CelestialConjureType::PUBLIC);
        $npc   = Npc::where('type', NpcTypes::SUMMONER)->first();

        if ($type->isPrivate()) {
            event(new GlobalMessageEvent($monster->name . ' has been conjured to the ' . $plane . ' plane.'));

            broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('location_of_conjure', $npc, $celestialFight), true));
        } else if ($type->isPublic()) {
            event(new GlobalMessageEvent( $monster->name . ' has been conjured to the ' . $plane . ' plane at (x/y): ' . $x . '/' . $y));
        }

        event(new UpdateMapDetailsBroadcast($character->map, $character->user, resolve(MovementService::class)));

        if ($damagedKingdom) {
            $this->damageKingdom($kingdom, $character, $this->getDamageAmount());
        }
    }

    public function getXPosition(): int {
        return CoordinatesCache::getFromCache()['x'][rand(CoordinatesCache::getFromCache()['x'][0], (count(CoordinatesCache::getFromCache()['x']) - 1))];
    }

    public function getYPosition(): int {
        return CoordinatesCache::getFromCache()['y'][rand(CoordinatesCache::getFromCache()['y'][0], (count(CoordinatesCache::getFromCache()['y']) - 1))];
    }

    public function canAfford(Monster $monster, Character $character) {
        if ($monster->gold_cost > $character->gold || $monster->gold_dust_cost > $character->gold_dust) {
            return false;
        }

        return true;
    }

    public function handleCost(Monster $monster, Character $character) {
        $character->update([
            'gold'      => $character->gold - $monster->gold_cost,
            'gold_dust' => $character->gold_dust - $monster->gold_dust_cost,
        ]);

        $user = $character->user;
        $characterMapId = $character->map->game_map_id;

        $character = new Item($character->refresh(), $this->characterTransformer);
        $character = $this->manager->createData($character)->toArray();
        $npc       = Npc::where('type', NpcTypes::SUMMONER)->where('game_map_id', $characterMapId)->first();

        event(new UpdateTopBarBroadcastEvent($character, $user));

        return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('paid_conjuring', $npc), true));
    }

    public function canDamageKingdom(): bool {
        return rand(0, self::DAMAGE_KD_CHECK) > (self::DAMAGE_KD_CHECK - 1);
    }

    protected function isAtKingdom(int $x, int $y) {
        return Kingdom::where('x_position', $x)
                      ->where('y_position', $y)
                      ->first();
    }

    protected function getDamageAmount(): float {
        return rand(1, 45) / 100;
    }

    protected function damageKingdom(Kingdom $kingdom, Character $character, float $damage) {
        $kingdom->buildings->each(function($building) use($damage) {
            $durability = floor($building->current_durability - ($building->current_durability * $damage));

            $building->update([
                'current_durability' => $durability
            ]);
        });

        $kingdom->units->each(function($unit) use($damage) {
            $newAmount = floor($unit->amount - ($unit->amount * $damage));

            $unit->update([
                'amount' => $newAmount
            ]);
        });

        $morale   = $kingdom->current_morale - ($kingdom->current_morale * $damage);
        $treasure = $kingdom->treasure - ($kingdom->treasure * $damage);

        $newMorale = floor($morale * 100);

        if (!($newMorale > 0)) {
            $morale = 0;
        }

        $kingdom->update([
            'current_morale' => $morale,
            'treasury'       => $treasure,
        ]);

        $kingdom = $kingdom->refresh();

        $kingdomPlane = $kingdom->gameMap->name;

        if (is_null($kingdom->character_id)) {
            $kingdomOwner = Npc::where('type', NpcTypes::KINGDOM_HOLDER)->first()->real_name;
        } else {
            $kingdomOwner = $kingdom->character->name;
        }

        $kingdomData = new Item($kingdom, $this->kingdomTransformer);
        $kingdomData = $this->manager->createData($kingdomData)->toArray();

        if (!is_null($kingdom->character_id)) {
            event(new UpdateKingdom($character->user, $kingdomData));
        }

        event(new GlobalMessageEvent($kingdomOwner . '\'s Kingdom on the ' . $kingdomPlane . ' plane was attacked by the Celestial Entity for: ' . ($damage * 100) . '%'));
    }
}
