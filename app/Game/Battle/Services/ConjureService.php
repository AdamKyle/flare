<?php

namespace App\Game\Battle\Services;

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

    public function conjure(Monster $monster, Character $character, string $type) {
        $x = CoordinatesCache::getFromCache()['x'][rand(CoordinatesCache::getFromCache()['x'][0], (count(CoordinatesCache::getFromCache()['x']) - 1))];
        $y = CoordinatesCache::getFromCache()['y'][rand(CoordinatesCache::getFromCache()['y'][0], (count(CoordinatesCache::getFromCache()['y']) - 1))];

        $kingdom = $this->isAtKingdom($x, $y);
        $damagedKingdom = false;

        if (!is_null($kingdom)) {
            if ($this->canDamageKingdom()) {
                $damagedKingdom = true;
            }
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

            return broadcast(new ServerMessageEvent($character->user, $this->npcServerMessageBuilder->build('location_of_conjure', $npc, $celestialFight), true));
        } else if ($type->isPublic()) {
            event(new GlobalMessageEvent( $monster->name . ' has been conjured to the ' . $plane . ' plane at (x/y): ' . $x . '/' . $y));
        }

        if ($damagedKingdom) {
            $this->damageKingdom($kingdom, $character, $this->getDamageAmount());
        }
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

        $character = new Item($character->refresh(), $this->characterTransformer);
        $character = $this->manager->createData($character)->toArray();
        $npc       = Npc::where('type', NpcTypes::SUMMONER)->first();

        event(new UpdateTopBarBroadcastEvent($character, $user));

        return broadcast(new ServerMessageEvent($user, $this->npcServerMessageBuilder->build('paid_conjuring', $npc), true));
    }

    protected function isAtKingdom(int $x, int $y) {
        return Kingdom::where('x_position', $x)
                      ->where('y_position', $y)
                      ->first();
    }

    protected function canDamageKingdom(): bool {
        return rand(0, self::DAMAGE_KD_CHECK) > (self::DAMAGE_KD_CHECK - 1);
    }

    protected function getDamageAmount(): float {
        return rand(1, 45) / 100;
    }

    protected function damageKingdom(Kingdom $kingdom, Character $character, float $damage) {
        $kingdom->buildings->each(function($building) use($damage) {
            $durability = $building->current_durability - ($building->current_durability * $damage);

            if ($durability < 0) {
                $durability = 0;
            }

            $building->update([
                'current_durability' => $durability
            ]);
        });

        $kingdom->units->each(function($unit) use($damage) {
            $newAmount = $unit->amount - ($unit->amount * $damage);

            if ($newAmount < 0) {
                $newAmount = 0;
            }

            $unit->update([
                'amount' => $newAmount
            ]);
        });

        $morale   = $kingdom->current_morale - ($kingdom->current_morale * $damage);
        $treasure = $kingdom->treasure - ($kingdom->treasure * $damage);

        if ($morale < 0) {
            $morale = 0;
        }

        $kingdom->update([
            'current_morale' => $morale,
            'treasury'       => $treasure,
        ]);

        $kingdom = $kingdom->refresh();

        $kingdomPlane = $kingdom->gameMap->name;

        if (is_null($kingdom->character_id)) {
            $kingdomOwner = Npc::where('type', NpcTypes::SUMMONER)->first()->real_name;
        } else {
            $kingdomOwner = $kingdom->character->name;
        }

        $kingdom = new Item($kingdom, $this->kingdomTransformer);
        $kingdom = $this->manager->createData($kingdom)->toArray();

        event(new UpdateKingdom($character->user, $kingdom));

        event(new GlobalMessageEvent($kingdomOwner . '\'s Kingdom on the ' . $kingdomPlane . ' plane was attacked by the Celestial Entity for: ' . ($damage * 100) . '%'));
    }
}
