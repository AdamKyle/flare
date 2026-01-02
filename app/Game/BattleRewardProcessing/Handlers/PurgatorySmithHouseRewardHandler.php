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

    public function handleFightingAtPurgatorySmithHouse(Character $character, Monster $monster, int $killCount = 1): Character
    {
        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location) || is_null($location->locationType())) {
            return $character;
        }

        if (! $location->locationType()->isPurgatorySmithHouse()) {
            return $character;
        }

        $event = Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->first();

        $character = $this->currencyReward($character, $event, $killCount);

        if ($character->currentAutomations->isNotEmpty()) {
            return $character;
        }

        $shouldAttemptLegendary = $this->isMonsterAtLeastHalfWayOrMore($location, $monster);
        $shouldAttemptMythic = $this->isMonsterTheFinalMonster($location, $monster);

        if ($shouldAttemptLegendary) {
            $this->attemptLegendaryRewardsForKillCount($character, $event, $killCount);
        }

        if ($shouldAttemptMythic) {
            $this->attemptMythicRewardsForKillCountCappedToOne($character, $event, $killCount);
        }

        if ($shouldAttemptLegendary || $shouldAttemptMythic) {
            $this->createPossibleEvent($killCount);
        }

        return $character->refresh();
    }

    protected function isMonsterAtLeastHalfWayOrMore(Location $location, Monster $monster): bool
    {
        $monsters = Cache::get('monsters')[$location->name];

        $monsterCount = count($monsters);
        $halfWay = (int) ($monsterCount / 2);

        $position = array_search($monster->id, array_column($monsters, 'id'));

        return $position !== false && $position >= $halfWay;
    }

    protected function isMonsterTheFinalMonster(Location $location, Monster $monster): bool
    {
        $monsters = Cache::get('monsters')[$location->name];

        return $monsters[count($monsters) - 1]['id'] === $monster->id;
    }

    public function currencyReward(Character $character, ?Event $event = null, int $killCount = 1): Character
    {
        $maximumAmount = 1_000;

        if (! is_null($event)) {
            $maximumAmount = 5_000;
        }

        $goldDustToGain = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount;
        $shardsToGain = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount;

        $hasItemForCopperCoins = $character->inventory->slots->where('item.effect', ItemEffectsValue::GET_COPPER_COINS)->count() > 0;
        $copperCoinsToGain = 0;

        if ($hasItemForCopperCoins) {
            $copperCoinsToGain = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount;
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

    private function attemptLegendaryRewardsForKillCount(Character $character, ?Event $event, int $killCount): void
    {
        $this->attemptItemRewardsForKillCount($character, false, $event, $killCount, false);
    }

    private function attemptMythicRewardsForKillCountCappedToOne(Character $character, ?Event $event, int $killCount): void
    {
        $this->attemptItemRewardsForKillCount($character, true, $event, $killCount, true);
    }

    /**
     * @throws Exception
     */
    private function attemptItemRewardsForKillCount(Character $character, bool $isMythic, ?Event $event, int $killCount, bool $capToOneReward): void
    {
        for ($iterationIndex = 0; $iterationIndex < $killCount; $iterationIndex++) {
            if ($character->isInventoryFull()) {
                break;
            }

            $wasRewarded = $this->attemptItemReward($character, $isMythic, $event);

            if ($capToOneReward && $wasRewarded) {
                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function attemptItemReward(Character $character, bool $isMythic, ?Event $event): bool
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = $isMythic ? 1_000 : 5_00;

        if ($lootingChance > 0.15) {
            $lootingChance = 0.15;
        }

        if (! is_null($event)) {
            $lootingChance = .30;
            $maxRoll = (int) ($maxRoll / 2);
        }

        if (! DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
            return false;
        }

        if ($character->isInventoryFull()) {
            return false;
        }

        return $this->rewardForCharacter($character, $isMythic);
    }

    /**
     * @throws Exception
     */
    protected function rewardForCharacter(Character $character, bool $isMythic = false): bool
    {
        $item = Item::where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return false;
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

            event(new ServerMessageEvent($character->user, 'You found something MYTHICAL in the basement child: '.$item->affix_name, $slot->id));

            return true;
        }

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

        event(new ServerMessageEvent($character->user, 'You found something LEGENDARY in the basement child: '.$item->affix_name, $slot->id));

        return true;
    }

    protected function createPossibleEvent(int $killCount = 1): void
    {
        if (Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists()) {
            return;
        }

        $chancePercent = 10 + $killCount;

        $threshold = 100 - $chancePercent;

        if (RandomNumberGenerator::generateTrueRandomNumber(100) >= $threshold) {
            Event::create([
                'type' => EventType::PURGATORY_SMITH_HOUSE,
                'started_at' => now(),
                'ends_at' => now()->addHour(),
            ]);

            AnnouncementHandler::createAnnouncement('purgatory_house');

            event(new GlobalMessageEvent(
                'The floor boards creak and the cries of the children trapped in their own misery wale across the lands. '.
                '"Children of Tlessa, hear me as I lay bare my treasures for you to find in the depths of my own memories." echoes a familiar voice. '.
                'You recognise it. The Creator ...'
            ));
        }
    }
}
