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

class TheOldChurchRewardHandler
{

    public function __construct(private RandomAffixGenerator $randomAffixGenerator, private BattleMessageHandler $battleMessageHandler) {}

    public function handleFightingAtTheOldChurch(Character $character, Monster $monster): Character
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

        if (! $location->locationType()->isTheOldChurch()) {
            return $character;
        }

        $hasQuestItem = $character->inventory->slots->filter(function ($slot) {
            return $slot->item->effect === ItemEffectsValue::THE_OLD_CHURCH;
        })->isNotEmpty();

        if (! $hasQuestItem) {
            return $character;
        }

        $event = Event::where('type', EventType::THE_OLD_CHURCH)->first();

        $character = $this->currencyReward($character, $event);

        if ($this->isMonsterAtLeastHalfWayOrMore($location, $monster)) {
            $character = $this->handleItemReward($character, $event);
        }

        return $character;
    }

    /**
     * is the monster at least halfway down the list?
     */
    private function isMonsterAtLeastHalfWayOrMore(Location $location, Monster $monster): bool
    {

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
     */
    public function currencyReward(Character $character, ?Event $event = null): Character
    {
        $maximumAmount = 1_000;
        $maximumGold = 20_000;

        if (! is_null($event)) {
            $maximumAmount = 5_000;
            $maximumGold = 40_000;
        }

        $goldDustToReward = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);
        $shardsToReward = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount);
        $goldToReward = RandomNumberGenerator::generateRandomNumber(1, $maximumGold);

        $gold = $character->gold + $goldToReward;
        $goldDust = $character->gold_dust + $goldDustToReward;
        $shards = $character->shards + $shardsToReward;

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

        $character = $character->refresh();

        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::GOLD, $goldToReward, $character->gold);
        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::GOLD_DUST, $goldDustToReward, $character->gold_dust);
        $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::SHARDS, $shardsToReward, $character->shards);

        return $character;
    }

    /**
     * Handle item Reward for player.
     *
     * @param  bool  $isMythic
     *
     * @throws Exception
     */
    private function handleItemReward(Character $character, ?Event $event = null): Character
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = 1_000;

        if ($lootingChance > 0.15) {
            $lootingChance = 0.15;
        }

        if (! is_null($event)) {
            $lootingChance = .30;
            $maxRoll = $maxRoll / 2;
        }

        if (DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
            if (! $character->isInventoryFull()) {
                $this->rewardForCharacter($character);
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
    private function rewardForCharacter(Character $character)
    {
        $item = Item::where('specialty_type', ItemSpecialtyType::CORRUPTED_ICE)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return;
        }

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::LEGENDARY);

        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ]);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $newItem->id,
        ]);

        event(new ServerMessageEvent($character->user, 'You found something unique, in The Old Church child: ' . $item->affix_name, $slot->id));
    }

    /**
     * 1 out of 1 million chance to create an event.
     *
     * @return void
     */
    private function createPossibleEvent()
    {

        if (Event::where('type', EventType::THE_OLD_CHURCH)->exists()) {
            return;
        }

        if (RandomNumberGenerator::generateTrueRandomNumber(1000000) >= 1000000) {
            Event::create([
                'type' => EventType::THE_OLD_CHURCH,
                'started_at' => now(),
                'ends_at' => now()->addHour(),
            ]);

            AnnouncementHandler::createAnnouncement('the_old_house');

            event(new GlobalMessageEvent(
                'The shadows of the past come to dance and finally you are able to see the light of the answers as
                The Emerald Prince appears before you.'
            ));
        }
    }
}
