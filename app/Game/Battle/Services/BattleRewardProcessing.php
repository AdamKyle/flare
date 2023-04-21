<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Event;
use App\Flare\Models\Location;
use App\Flare\Values\EventType;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\LocationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Map;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Game\Battle\Handlers\FactionHandler;
use App\Game\Battle\Jobs\BattleItemHandler;
use App\Game\Core\Services\DropCheckService;
use App\Game\Core\Services\GoldRush;
use App\Game\Core\Traits\MercenaryBonus;
use App\Game\Mercenaries\Values\MercenaryValue;
use Exception;

class BattleRewardProcessing {

    use MercenaryBonus;

    private FactionHandler $factionHandler;

    private CharacterRewardService $characterRewardService;

    private GoldRush $goldRushService;

    public function __construct(FactionHandler $factionHandler, CharacterRewardService $characterRewardService, GoldRush $goldRush) {
        $this->factionHandler         = $factionHandler;
        $this->characterRewardService = $characterRewardService;
        $this->goldRushService        = $goldRush;
    }

    public function handleMonster(Character $character, Monster $monster) {
        $map     = Map::where('character_id', $character->id)->first();
        $gameMap = GameMap::find($map->game_map_id);

        if (!$gameMap->mapType()->isPurgatory()) {
            $this->factionHandler->handleFaction($character, $monster);
        }

        $this->characterRewardService->setCharacter($character->refresh())->distributeGoldAndXp($monster);

        $character = $this->characterRewardService->getCharacter();

        BattleItemHandler::dispatch($character, $monster);

        $this->goldRushService->processPotentialGoldRush($character, $monster);

        $character = $this->giveShards($character);

        $character = $this->currencyEventReward($character, $monster);

        event(new UpdateTopBarEvent($character));
    }

    protected function currencyEventReward(Character $character, Monster $monster): Character {
        $event = Event::where('type', EventType::WEEKLY_CURRENCY_DROPS)->first();

        if (!is_null($event) && !$monster->is_celestial_entity) {

            $canHaveCopperCoins = $character->inventory->slots->filter(function($slot) {
                return $slot->item->effect === ItemEffectsValue::GET_COPPER_COINS;
            })->isNotEmpty();

            $shards = rand(1,50);
            $shards = $shards + $shards * $this->getShardBonus($character);

            $goldDust = rand(1,50);
            $goldDust = $goldDust + $goldDust * $this->getGoldDustBonus($character);

            $characterShards      = $character->shards + $shards;
            $characterGoldDust    = $character->gold_dust + $goldDust;

            if ($canHaveCopperCoins) {
                $copperCoins = rand(1,50);
                $copperCoins = $copperCoins + $copperCoins * $this->getCopperCoinBonus($character);

                $characterCopperCoins = $character->copper_coins + $copperCoins;
            } else {
                $characterCopperCoins = $character->copper_coins;
            }

            if ($characterShards > MaxCurrenciesValue::MAX_SHARDS) {
                $characterShards = MaxCurrenciesValue::MAX_SHARDS;
            }

            if ($characterCopperCoins > MaxCurrenciesValue::MAX_COPPER) {
                $characterCopperCoins = MaxCurrenciesValue::MAX_COPPER;
            }

            if ($characterGoldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
                $characterGoldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
            }

            $character->update([
                'shards'       => $characterShards,
                'copper_coins' => $characterCopperCoins,
                'gold_dust'    => $characterGoldDust
            ]);
        }

        return $character->refresh();
    }

    /**
     * Give character shards.
     *
     * - Only if they are at a special location and in gold mines.
     *
     * @param Character $character
     * @return Character
     * @throws Exception
     */
    protected function giveShards(Character $character): Character {
        $specialLocation = $this->findLocationWithEffect($character->map);

        if (!is_null($specialLocation)) {
            if (!is_null($specialLocation->type)) {
                $locationType = new LocationType($specialLocation->type);

                if ($locationType->isGoldMines()) {
                    $shards = rand(1,25);

                    $shards = $shards + $shards * $this->getShardBonus($character);

                    $newShards = $character->shards + $shards;

                    if ($newShards > MaxCurrenciesValue::MAX_SHARDS) {
                        $newShards = MaxCurrenciesValue::MAX_SHARDS;
                    }

                    $character->update(['shards' => $newShards]);
                }
            }
        }

        return $character->refresh();
    }

    /**
     * Are we at a location with an effect (special location)?
     *
     * @param Map $map
     * @return void
     */
    protected function findLocationWithEffect(Map $map) {
        return Location::whereNotNull('enemy_strength_type')
                       ->where('x', $map->character_position_x)
                       ->where('y', $map->character_position_y)
                       ->where('game_map_id', $map->game_map_id)
                       ->first();
    }

}
