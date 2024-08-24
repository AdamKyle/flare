<?php

namespace App\Game\Events\Services;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Item;
use App\Flare\Models\Kingdom;
use App\Flare\Values\ArmourTypes;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\SpellTypes;
use App\Flare\Values\WeaponTypes;
use App\Game\Kingdoms\Handlers\Traits\DestroyKingdom;
use App\Game\Messages\Events\GlobalMessageEvent;

class KingdomEventService
{
    use DestroyKingdom;

    public function handleKingdomRewardsForEvent(string $gameMapName)
    {
        $gameMap = GameMap::where('name', $gameMapName)->first();

        $character = Character::select('characters.*')
            ->join('kingdoms', 'kingdoms.character_id', '=', 'characters.id')
            ->where('kingdoms.game_map_id', $gameMap->id)
            ->groupBy('characters.id')
            ->orderByRaw('COUNT(kingdoms.id) DESC')
            ->selectRaw('COUNT(kingdoms.id) as kingdom_count')
            ->first();

        if (! is_null($character)) {
            event(new GlobalMessageEvent('Congratulations to: '.$character->name.' for being the character with the most kingdoms on '.$gameMapName.'! They have been rewarded with something beyond their imagination!'));

            $this->giveFullSetToWinner($character, $gameMap);
        }

        $gameMapNameValue = new MapNameValue($gameMapName);

        if ($gameMapNameValue->isTheIcePlane()) {
            event(new GlobalMessageEvent('The Queen Rages with her magics as she causes the ground and the ice to heave, crack and shatter taking the kingdoms with it! The people, their screams. Her laughter fills the air!'));
        }

        if ($gameMapNameValue->isDelusionalMemories()) {
            event(new GlobalMessageEvent('The Holy Knights of The Church march into the kingdoms settled by those around. "Heretics! All Heretics!" they burn, they kill, they pillage!'));
        }

        Kingdom::where('game_map_id', $gameMap->id)->chunkById(100, function ($kingdoms) {
            foreach ($kingdoms as $kingdom) {
                $this->destroyKingdom($kingdom);
            }
        });
    }

    protected function giveFullSetToWinner(Character $character, GameMap $gameMap)
    {
        $types = [
            WeaponTypes::BOW,
            WeaponTypes::HAMMER,
            WeaponTypes::RING,
            WeaponTypes::RING,
            WeaponTypes::STAVE,
            WeaponTypes::WEAPON,
            WeaponTypes::WEAPON,
            SpellTypes::HEALING,
            SpellTypes::DAMAGE,
            ArmourTypes::BODY,
            ArmourTypes::FEET,
            ArmourTypes::GLOVES,
            ArmourTypes::HELMET,
            ArmourTypes::LEGGINGS,
            ArmourTypes::SHIELD,
            ArmourTypes::SHIELD,
            ArmourTypes::SLEEVES,
        ];

        foreach ($types as $type) {

            if ($gameMap->mapType()->isTheIcePlane()) {
                $character = $this->giveItemsOfType($character, ItemSpecialtyType::CORRUPTED_ICE, $type);
            }

            if ($gameMap->mapType()->isDelusionalMemories()) {
                $character = $this->giveItemsOfType($character, ItemSpecialtyType::DELUSIONAL_SILVER, $type);
            }
        }
    }

    private function giveItemsOfType(Character $character, string $itemSpecialtyType, string $type): Character
    {
        $item = Item::whereNull('item_suffix_id')
            ->whereNull('item_prefix_id')
            ->where('specialty_type', $itemSpecialtyType)
            ->where('type', $type)
            ->first();

        if (! is_null($item)) {
            $item = $item->duplicate();

            $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $item->id,
            ]);

            return $character->refresh();
        }

        return $character;
    }
}
