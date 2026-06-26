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
use App\Game\Messages\Types\CurrenciesMessageTypes;
use Exception;
use Facades\App\Flare\Calculators\DropCheckCalculator;
use Facades\App\Flare\RandomNumber\RandomNumberGenerator;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;

class PurgatorySmithHouseRewardHandler
{
    private array $earnedCurrencies = [];

    public function __construct(private RandomAffixGenerator $randomAffixGenerator, private BattleMessageHandler $battleMessageHandler) {}

    public function getEarnedCurrencies(): array
    {
        return $this->earnedCurrencies;
    }

    public function handleFightingAtPurgatorySmithHouse(Character $character, Monster $monster, int $killCount = 1): Character
    {
        $this->earnedCurrencies = [];
        $plan = $this->planFightingAtPurgatorySmithHouse($character, $monster, $killCount);

        if (! $plan['applies']) {
            return $character;
        }

        $this->applyPlannedPurgatorySmithHouseReward($character, $plan);

        return $character->refresh();
    }

    public function planFightingAtPurgatorySmithHouse(Character $character, Monster $monster, int $killCount = 1, array $context = []): array
    {
        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location) || is_null($location->locationType())) {
            return $this->noopPlan($character, $monster, $killCount, $context, 'missing_location');
        }

        if (! $location->locationType()->isPurgatorySmithHouse()) {
            return $this->noopPlan($character, $monster, $killCount, $context, 'not_purgatory_smith_house');
        }

        $event = Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->first();
        $currencyPlan = $this->planCurrencyReward($character, $event, $killCount);
        $itemPlans = [];
        $shouldAttemptLegendary = $character->currentAutomations->isEmpty() && $this->isMonsterAtLeastHalfWayOrMore($location, $monster);
        $shouldAttemptMythic = $character->currentAutomations->isEmpty() && $this->isMonsterTheFinalMonster($location, $monster);

        if ($shouldAttemptLegendary) {
            $itemPlans = array_merge($itemPlans, $this->planItemRewards($character, $monster, false, $event, $killCount, false));
        }

        if ($shouldAttemptMythic) {
            $itemPlans = array_merge($itemPlans, $this->planItemRewards($character, $monster, true, $event, $killCount, true));
        }

        return [
            'handler' => 'purgatory_smith_house',
            'applies' => true,
            'noop' => false,
            'request_id' => $context['request_id'] ?? null,
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'kill_count' => $killCount,
            'location' => [
                'id' => $location->id,
                'type' => $location->type,
                'name' => $location->name,
                'x' => $location->x,
                'y' => $location->y,
                'game_map_id' => $location->game_map_id,
            ],
            'event' => $shouldAttemptLegendary || $shouldAttemptMythic ? $this->planPossibleEvent($killCount) : ['create' => false],
            'currencies' => $currencyPlan,
            'items' => $itemPlans,
        ];
    }

    public function applyPlannedPurgatorySmithHouseReward(Character $character, array $plan): array
    {
        if (! ($plan['applies'] ?? false)) {
            return ['noop' => true, 'currencies' => [], 'item_count' => 0, 'event_created' => false];
        }

        $this->earnedCurrencies = $this->applyPlannedCurrencies($character, $plan['currencies'] ?? []);
        $itemCount = $this->applyPlannedItems($character->refresh(), $plan['items'] ?? []);
        $eventCreated = $this->applyPlannedEvent($plan['event'] ?? []);

        return [
            'noop' => false,
            'currencies' => $this->earnedCurrencies,
            'item_count' => $itemCount,
            'event_created' => $eventCreated,
        ];
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
        $this->earnedCurrencies = $this->applyPlannedCurrencies($character, $this->planCurrencyReward($character, $event, $killCount));

        return $character->refresh();
    }

    private function attemptLegendaryRewardsForKillCount(Character $character, Monster $monster, ?Event $event, int $killCount): void
    {
        $this->attemptItemRewardsForKillCount($character, $monster, false, $event, $killCount, false);
    }

    private function attemptMythicRewardsForKillCountCappedToOne(Character $character, Monster $monster, ?Event $event, int $killCount): void
    {
        $this->attemptItemRewardsForKillCount($character, $monster, true, $event, $killCount, true);
    }

    /**
     * @throws Exception
     */
    private function attemptItemRewardsForKillCount(Character $character, Monster $monster, bool $isMythic, ?Event $event, int $killCount, bool $capToOneReward): void
    {
        for ($iterationIndex = 0; $iterationIndex < $killCount; $iterationIndex++) {
            if ($character->isInventoryFull()) {
                break;
            }

            $wasRewarded = $this->attemptItemReward($character, $monster, $isMythic, $event);

            if ($capToOneReward && $wasRewarded) {
                break;
            }
        }
    }

    /**
     * @throws Exception
     */
    private function attemptItemReward(Character $character, Monster $monster, bool $isMythic, ?Event $event): bool
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = $isMythic ? 1_000 : 5_00;
        $maximumChance = 0.30;

        if ($lootingChance > 0.15) {
            $lootingChance = 0.15;
        }

        if (! is_null($event)) {
            $lootingChance = .30;
            $maxRoll = (int) ($maxRoll / 2);
            $maximumChance = 0.45;
        }

        $lootingChance = min($lootingChance + ($monster->drop_check * 0.25), $maximumChance);

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

            ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found something MYTHICAL in the basement child: ' . $item->affix_name, $slot->id);

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

        ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found something LEGENDARY in the basement child: ' . $item->affix_name, $slot->id);

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
                'The floor boards creak and the cries of the children trapped in their own misery wale across the lands. ' .
                '"Children of Tlessa, hear me as I lay bare my treasures for you to find in the depths of my own memories." echoes a familiar voice. ' .
                'You recognise it. The Creator ...'
            ));
        }
    }

    private function noopPlan(Character $character, Monster $monster, int $killCount, array $context, string $reason): array
    {
        return [
            'handler' => 'purgatory_smith_house',
            'applies' => false,
            'noop' => true,
            'reason' => $reason,
            'request_id' => $context['request_id'] ?? null,
            'character_id' => $character->id,
            'monster_id' => $monster->id,
            'kill_count' => $killCount,
        ];
    }

    private function planCurrencyReward(Character $character, ?Event $event, int $killCount): array
    {
        $maximumAmount = is_null($event) ? 750 : 3_750;
        $amounts = [
            'gold_dust' => RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount,
            'shards' => RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount,
            'copper_coins' => 0,
        ];

        $hasItemForCopperCoins = $character->inventory->slots->where('item.effect', ItemEffectsValue::GET_COPPER_COINS)->count() > 0;

        if ($hasItemForCopperCoins) {
            $amounts['copper_coins'] = RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount;
        }

        return $this->currencyPlanFromAmounts($character, $amounts);
    }

    private function currencyPlanFromAmounts(Character $character, array $amounts): array
    {
        $maximums = [
            'gold' => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust' => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards' => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER,
        ];
        $starting = [];
        $target = [];

        foreach ($amounts as $currency => $amount) {
            $starting[$currency] = (int) $character->getAttribute($currency);
            $target[$currency] = min($maximums[$currency], $starting[$currency] + $amount);
        }

        return [
            'amounts' => $amounts,
            'starting' => $starting,
            'target' => $target,
        ];
    }

    private function applyPlannedCurrencies(Character $character, array $currencyPlan): array
    {
        $applied = [];
        $updates = [];

        foreach (($currencyPlan['amounts'] ?? []) as $currency => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $current = (int) $character->getAttribute($currency);
            $target = (int) ($currencyPlan['target'][$currency] ?? $current);

            if ($current >= $target) {
                continue;
            }

            $updates[$currency] = $target;
            $applied[$currency] = $amount;
        }

        if ($updates === []) {
            return [];
        }

        $character->update($updates);
        $character = $character->refresh();

        foreach ($applied as $currency => $amount) {
            $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::from($currency), $amount, (int) $character->getAttribute($currency));
        }

        return $applied;
    }

    private function planItemRewards(Character $character, Monster $monster, bool $isMythic, ?Event $event, int $killCount, bool $capToOneReward): array
    {
        $items = [];
        $remainingSlots = max(0, $character->inventory_max - $character->getInventoryCount());

        for ($iterationIndex = 0; $iterationIndex < $killCount && count($items) < $remainingSlots; $iterationIndex++) {
            $itemPlan = $this->planItemRewardAttempt($character, $monster, $isMythic, $event);

            if (is_null($itemPlan)) {
                continue;
            }

            $items[] = $itemPlan;

            if ($capToOneReward) {
                break;
            }
        }

        return $items;
    }

    private function planItemRewardAttempt(Character $character, Monster $monster, bool $isMythic, ?Event $event): ?array
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = $isMythic ? 1_000 : 5_00;
        $maximumChance = 0.30;

        if ($lootingChance > 0.15) {
            $lootingChance = 0.15;
        }

        if (! is_null($event)) {
            $lootingChance = .30;
            $maxRoll = (int) ($maxRoll / 2);
            $maximumChance = 0.45;
        }

        $lootingChance = min($lootingChance + ($monster->drop_check * 0.25), $maximumChance);

        if (! DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
            return null;
        }

        return $this->planItemReward($character, $isMythic);
    }

    private function planItemReward(Character $character, bool $isMythic): ?array
    {
        $item = Item::where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return null;
        }

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount($isMythic ? RandomAffixDetails::MYTHIC : RandomAffixDetails::LEGENDARY);
        $newItem = $item->duplicate();
        $updates = [
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ];

        if ($isMythic) {
            $updates['is_mythic'] = true;
        }

        $newItem->update($updates);

        return [
            'base_item_id' => $item->id,
            'planned_item_id' => $newItem->id,
            'is_mythic' => $isMythic,
            'message' => $isMythic
                ? 'You found something MYTHICAL in the basement child: ' . $item->affix_name
                : 'You found something LEGENDARY in the basement child: ' . $item->affix_name,
        ];
    }

    private function applyPlannedItems(Character $character, array $items): int
    {
        $applied = 0;

        foreach ($items as $itemPlan) {
            $existingSlot = $character->inventory->slots()
                ->where('item_id', $itemPlan['planned_item_id'])
                ->first();

            if (! is_null($existingSlot)) {
                continue;
            }

            $slot = $character->inventory->slots()->create([
                'inventory_id' => $character->inventory->id,
                'item_id' => $itemPlan['planned_item_id'],
            ]);

            ServerMessageHandler::sendBasicMessageWithId($character->user, $itemPlan['message'], $slot->id);
            $applied++;
        }

        return $applied;
    }

    private function planPossibleEvent(int $killCount): array
    {
        if (Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists()) {
            return ['create' => false, 'type' => EventType::PURGATORY_SMITH_HOUSE, 'reason' => 'active_event_exists'];
        }

        $chancePercent = 10 + $killCount;
        $threshold = 100 - $chancePercent;

        return [
            'create' => RandomNumberGenerator::generateTrueRandomNumber(100) >= $threshold,
            'type' => EventType::PURGATORY_SMITH_HOUSE,
            'announcement' => 'purgatory_house',
            'message' => 'The floor boards creak and the cries of the children trapped in their own misery wale across the lands. ' .
                '"Children of Tlessa, hear me as I lay bare my treasures for you to find in the depths of my own memories." echoes a familiar voice. ' .
                'You recognise it. The Creator ...',
        ];
    }

    private function applyPlannedEvent(array $eventPlan): bool
    {
        if (! ($eventPlan['create'] ?? false)) {
            return false;
        }

        if (Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists()) {
            return false;
        }

        Event::create([
            'type' => EventType::PURGATORY_SMITH_HOUSE,
            'started_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        AnnouncementHandler::createAnnouncement($eventPlan['announcement']);
        event(new GlobalMessageEvent($eventPlan['message']));

        return true;
    }
}
