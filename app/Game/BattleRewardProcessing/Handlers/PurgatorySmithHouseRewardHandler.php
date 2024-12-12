<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Flare\Values\ItemEffectsValue;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Exception;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Illuminate\Support\Facades\Cache;

class PurgatorySmithHouseRewardHandler
{

    public function __construct(private RandomAffixGenerator $randomAffixGenerator, private BattleMessageHandler $battleMessageHandler) {}

    public function handleFightingAtPurgatorySmithHouse(Character $character, Monster $monster): Character
    {

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

        if (! $location->locationType()->isPurgatorySmithHouse()) {
            return $character;
        }

        $event = Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->first();

        $character = $this->currencyReward($character, $event);

        if ($this->isMonsterAtLeastHalfWayOrMore($location, $monster)) {
            $character = $this->handleItemReward($character, false, $event);
        }

        if ($this->isMonsterTheFinalMonster($location, $monster)) {
            $character = $this->handleItemReward($character, true, $event);
        }

        return $character;
    }

    /**
     * is the monster at least halfway down the list?
     */
    protected function isMonsterAtLeastHalfWayOrMore(Location $location, Monster $monster): bool
    {

        $monsters = Cache::get('monsters')[$location->name];

        $monsterCount = count($monsters);
        $halfWay = (int) ($monsterCount / 2);

        $position = array_search($monster->id, array_column($monsters, 'id'));

        return $position !== false && $position >= $halfWay;
    }

    /**
     * is the monster the final monster?
     */
    protected function isMonsterTheFinalMonster(Location $location, Monster $monster): bool
    {
        $monsters = Cache::get('monsters')[$location->name];

        return $monsters[count($monsters) - 1]['id'] === $monster->id;
    }

    /**
     * Reward the character with currencies.
     *
     * - Only gives copper coins if the character has
     */
    public function currencyReward(Character $character, ?Event $event = null): Character
    {
        $maximumAmount = 1_000;

        if (! is_null($event)) {
            $maximumAmount = 5_000;
        }

        $goldDustToGain = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);
        $shardsToGain = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);

        $hasItemForCopperCoins = $character->inventory->slots->where('item.effect', ItemEffectsValue::GET_COPPER_COINS)->count() > 0;
        $copperCoinsToGain = 0;

        if ($hasItemForCopperCoins) {
            $copperCoinsToGain = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);
        }

        $goldDust = $character->gold_dust + $goldDustToGain;
        $shards = $character->shards + $shardsToGain;
        $copperCoins = $character->copper_coins + $copperCoinsToGain;

        if ($goldDust > MaxCurrenciesValue::MAX_GOLD_DUST) {
            $goldDust = MaxCurrenciesValue::MAX_GOLD_DUST;
        }

        if ($shards > MaxCurrenciesValue::MAX_SHARDS) {
            $shards = MaxCurrenciesValue::MAX_SHARDS;
        }

        if ($copperCoins > MaxCurrenciesValue::MAX_COPPER) {
            $copperCoins = MaxCurrenciesValue::MAX_COPPER;
        }

        $character->update([
            'gold_dust' => $goldDust,
            'shards' => $shards,
            'copper_coins' => $copperCoins,
        ]);

        $character = $character->refresh();

        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::GOLD_DUST, $goldDustToGain, $character->gold_dust);
        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::SHARDS, $shardsToGain, $character->shards);

        if ($copperCoinsToGain > 0) {
            $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::COPPER_COINS, $copperCoinsToGain, $character->copper_coins);
        }

        return $character;
    }

    /**
     * Handle item Reward for player.
     *
     * @throws Exception
     */
    protected function handleItemReward(Character $character, bool $isMythic, ?Event $event = null): Character
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = $isMythic ? 1_000 : 5_00;

        if ($lootingChance > 0.15) {
            $lootingChance = 0.15;
        }

        if (! is_null($event)) {
            $lootingChance = .30;
            $maxRoll = $maxRoll / 2;
        }

        if (DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
            if (! $character->isInventoryFull()) {
                $this->rewardForCharacter($character, $isMythic);
            }
        }

        $this->createPossibleEvent();

        return $character->refresh();
    }

    /**
     * Reward player with item.
     *
     * @return void
     *
     * @throws Exception
     */
    protected function rewardForCharacter(Character $character, bool $isMythic = false)
    {
        $item = Item::where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        if (! $isMythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)
                ->setPaidAmount(RandomAffixDetails::LEGENDARY);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
            ]);

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You found something LEGENDARY in the basement child: ' . $item->affix_name, $slot->id));
        }

        if ($isMythic) {
            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::MYTHIC);

            $newItem = $item->duplicate();

            $newItem->update([
                'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
                'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
                'is_mythic' => true,
            ]);

            $newItem = $newItem->refresh();

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $newItem->id,
            ]);

            event(new ServerMessageEvent($character->user, 'You found something MYTHICAL in the basement child: ' . $item->affix_name, $slot->id));
        }
    }

    /**
     * 1 out of 1 million chance to create an event.
     *
     * @return void
     */
    protected function createPossibleEvent()
    {

        if (Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists()) {
            return;
        }

        if (RandomNumberGenerator::generateTrueRandomNumber(1000000) >= 1000000) {
            Event::create([
                'type' => EventType::PURGATORY_SMITH_HOUSE,
                'started_at' => now(),
                'ends_at' => now()->addHour(),
            ]);

            AnnouncementHandler::createAnnouncement('purgatory_house');

            event(new GlobalMessageEvent(
                'The floor boards creak and the cries of the children trapped in their own misery wale across the lands. ' .
                    '"Children of Tlessa, hear me as I lay bare my treasures for you to find in the depths of my own memories." echoes a familiar voice. ' .
                    'You recognise it. The Creator ...'
            ));
        }
    }
}
