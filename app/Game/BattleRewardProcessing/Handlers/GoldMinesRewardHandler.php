<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Exception;
use Illuminate\Support\Facades\Cache;

class GoldMinesRewardHandler {


    /**
     * @var RandomAffixGenerator $randomAffixGenerator
     */
    private RandomAffixGenerator $randomAffixGenerator;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     */
    public function __construct(RandomAffixGenerator $randomAffixGenerator) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    public function handleFightingAtGoldMines(Character $character, Monster $monster): Character {

        if ($character->currentAutomations->isNotEmpty()) {
            return $character;
        }

        $location = Location::where('x', $character->x_position)
            ->where('y', $character->y_position)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location) || is_null($location->locationType())) {
            return $character;
        }

        if (!$location->locationType()->isGoldMines()) {
             return $character;
        }

        $event = Event::where('type', EventType::GOLD_MINES)->first();

        $character = $this->currencyReward($character, $event);

        if ($this->isMonsterAtLeastHalfWayOrMore($location, $monster)) {
            $character = $this->handleItemReward($character, $event);
        }

        return $character;
    }

    /**
     * is the monster at least halfway down the list?
     *
     * @param Location $location
     * @param Monster $monster
     * @return bool
     */
    protected function isMonsterAtLeastHalfWayOrMore(Location $location, Monster $monster): bool {

        $monsters = Cache::get('monsters')[$location->name];

        $monsterCount = count($monsters);
        $halfWay = (int) ($monsterCount / 2);

        $position = array_search($monster->id, array_column($monsters, 'id'));

        return $position !== false && $position >= $halfWay;
    }

    /**
     * Reward the character with currencies.
     *
     * - Only gives copper coins if the character has
     *
     * @param Character $character
     * @param Event|null $event
     * @return Character
     */
    public function currencyReward(Character $character, Event $event = null): Character {
        $maximumAmount = 500;
        $maximumGold = 10000;

        if (!is_null($event)) {
            $maximumAmount = 2000;
            $maximumGold = 20000;
        }

        $goldDust = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);
        $shards   = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);
        $gold     = RandomNumberGenerator::generateRandomNumber(1, $maximumGold);

        $gold        += $character->gold;
        $goldDust    += $character->gold_dust;
        $shards      += $character->shards;

        if ($goldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
            $goldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        if ($shards > MaxCurrenciesValue::MAX_SHARDS) {
            $shards = MaxCurrenciesValue::MAX_SHARDS;
        }

        if ($gold > MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update([
            'gold_dust' => $goldDust,
            'shards' => $shards,
            'gold' => $gold,
        ]);

        return $character->refresh();
    }

    /**
     * Handle item Reward for player.
     *
     * @param Character $character
     * @param Event|null $event
     * @return Character
     * @throws Exception
     */
    protected function handleItemReward(Character $character, Event $event = null): Character {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = 1000000;

        if ($lootingChance > 0.15) {
            $lootingChance = 0.15;
        }

        if (!is_null($event)) {
            $lootingChance = .30;
            $maxRoll = $maxRoll / 2;
        }

        if (DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
            if (!$character->isInventoryFull()) {
                $this->rewardForCharacter($character);
            }
        }

        $this->createPossibleEvent();

        return $character->refresh();
    }

    /**
     * Reward player with item.
     *
     * @param Character $character
     * @param bool $isMythic
     * @return void
     * @throws Exception
     */
    protected function rewardForCharacter(Character $character, bool $isMythic = false) {
        $item = Item::whereNull('specialty_type')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if (!$isMythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::MEDIUM);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id'      => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You found something MEDIUM but still unique, in the mines child: ' . $item->affix_name, $slot->id));
        }
    }

    /**
     * 1 out of 1 million chance to create an event.
     *
     * @return void
     */
    protected function createPossibleEvent() {

        if (Event::where('type', EventType::GOLD_MINES)->exists()) {
            return;
        }

        if (RandomNumberGenerator::generateTrueRandomNumber(1000000) >= 1000000) {
            Event::create([
                'type'        => EventType::GOLD_MINES,
                'started_at'  => now(),
                'ends_at'     => now()->addHour(),
            ]);

            AnnouncementHandler::createAnnouncement('gold_mines');

            event(new GlobalMessageEvent(
                'There comes a howling scream from the depths of the mines in the land of tormenting shadows.
                Someone released the vien of hate and flooded the mines with treasure!'
            ));
        }
    }
}
