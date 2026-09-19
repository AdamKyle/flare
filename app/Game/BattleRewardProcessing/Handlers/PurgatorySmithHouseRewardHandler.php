<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
use App\Game\BattleRewardProcessing\Values\BattleRewardSharedContext;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Events\Values\EventType;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Types\CurrenciesMessageTypes;
use App\Game\Monsters\Values\MonsterCacheKey;
use Exception;
use Facades\App\Game\Core\Handlers\AnnouncementHandler;
use Facades\App\Game\Messages\Handlers\ServerMessageHandler;
use Illuminate\Support\Facades\Cache;

class PurgatorySmithHouseRewardHandler
{
    private array $earnedCurrencies = [];

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param BattleMessageHandler $battleMessageHandler
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     */
    public function __construct(
        private RandomAffixGenerator $randomAffixGenerator,
        private BattleMessageHandler $battleMessageHandler,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
    ) {}

    /**
     * Return the currencies earned by the most recently applied reward.
     *
     * @return array
     */
    public function getEarnedCurrencies(): array
    {
        return $this->earnedCurrencies;
    }

    /**
     * Plan and immediately apply the Purgatory Smith House reward for the Character's fight.
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $killCount
     * @return Character
     */
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

    /**
     * Plan the Purgatory Smith House reward for the Character's fight without applying it.
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $killCount
     * @param array $context
     * @param ?BattleRewardSharedContext $sharedContext
     * @return array
     */
    public function planFightingAtPurgatorySmithHouse(Character $character, Monster $monster, int $killCount = 1, array $context = [], ?BattleRewardSharedContext $sharedContext = null): array
    {
        $locationSnapshot = $this->resolveLocationSnapshot($character, $sharedContext);

        if (is_null($locationSnapshot) || is_null($locationSnapshot['type'])) {
            return $this->noopPlan($character, $monster, $killCount, $context, 'missing_location');
        }

        if (! $locationSnapshot['type']->isPurgatorySmithHouse()) {
            return $this->noopPlan($character, $monster, $killCount, $context, 'not_purgatory_smith_house');
        }

        $event = Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->first();
        $currencyPlan = $this->planCurrencyReward($character, $event, $killCount);
        $itemPlans = [];
        $shouldAttemptLegendary = $character->currentAutomations->isEmpty() && $this->isMonsterAtLeastHalfWayOrMore($locationSnapshot['game_map_id'], $monster);
        $shouldAttemptMythic = $character->currentAutomations->isEmpty() && $this->isMonsterTheFinalMonster($locationSnapshot['game_map_id'], $monster);

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
                'id' => $locationSnapshot['id'],
                'type' => $locationSnapshot['type']->value,
                'name' => $locationSnapshot['name'],
                'x' => $locationSnapshot['x'],
                'y' => $locationSnapshot['y'],
                'game_map_id' => $locationSnapshot['game_map_id'],
            ],
            'event' => $shouldAttemptLegendary || $shouldAttemptMythic ? $this->planPossibleEvent($killCount) : ['create' => false],
            'currencies' => $currencyPlan,
            'items' => $itemPlans,
        ];
    }

    /**
     * Resolve the Character's current Location identity, reusing the request's
     * shared context when supplied instead of repeating the coordinate lookup.
     *
     * @param Character $character
     * @param ?BattleRewardSharedContext $sharedContext
     * @return ?array
     */
    private function resolveLocationSnapshot(Character $character, ?BattleRewardSharedContext $sharedContext): ?array
    {
        if (! is_null($sharedContext)) {
            if (is_null($sharedContext->locationId())) {
                return null;
            }

            return [
                'id' => $sharedContext->locationId(),
                'type' => $sharedContext->locationType(),
                'name' => $sharedContext->locationName(),
                'x' => $sharedContext->locationX(),
                'y' => $sharedContext->locationY(),
                'game_map_id' => $sharedContext->locationGameMapId(),
            ];
        }

        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location)) {
            return null;
        }

        return [
            'id' => $location->id,
            'type' => $location->locationType(),
            'name' => $location->name,
            'x' => $location->x,
            'y' => $location->y,
            'game_map_id' => $location->game_map_id,
        ];
    }

    /**
     * Apply a previously planned Purgatory Smith House reward to the Character.
     *
     * @param Character $character
     * @param array $plan
     * @return array
     */
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

    /**
     * Determine whether the Monster is at least halfway down the current Map's Monster list.
     *
     * @param int $gameMapId
     * @param Monster $monster
     * @return bool
     */
    private function isMonsterAtLeastHalfWayOrMore(int $gameMapId, Monster $monster): bool
    {
        $monsters = Cache::get(MonsterCacheKey::forGameMap($gameMapId)) ?? [];

        $monsterCount = count($monsters);
        $halfWay = intdiv($monsterCount, 2);

        $position = array_search($monster->id, array_column($monsters, 'id'));

        return $position !== false && $position >= $halfWay;
    }

    /**
     * Determine whether the Monster is the final Monster in the current Map's Monster list.
     *
     * @param int $gameMapId
     * @param Monster $monster
     * @return bool
     */
    private function isMonsterTheFinalMonster(int $gameMapId, Monster $monster): bool
    {
        $monsters = Cache::get(MonsterCacheKey::forGameMap($gameMapId)) ?? [];

        return $monsters[count($monsters) - 1]['id'] === $monster->id;
    }

    /**
     * Apply the Purgatory Smith House currency reward directly to the Character.
     *
     * @param Character $character
     * @param ?Event $event
     * @param int $killCount
     * @return Character
     */
    public function currencyReward(Character $character, ?Event $event = null, int $killCount = 1): Character
    {
        $this->earnedCurrencies = $this->applyPlannedCurrencies($character, $this->planCurrencyReward($character, $event, $killCount));

        return $character->refresh();
    }

    /**
     * Attempt the Legendary item reward once per kill in the batch.
     *
     * @param Character $character
     * @param Monster $monster
     * @param ?Event $event
     * @param int $killCount
     * @return void
     */
    private function attemptLegendaryRewardsForKillCount(Character $character, Monster $monster, ?Event $event, int $killCount): void
    {
        $this->attemptItemRewardsForKillCount($character, $monster, false, $event, $killCount, false);
    }

    /**
     * Attempt the Mythic item reward, capped to at most one award for the batch.
     *
     * @param Character $character
     * @param Monster $monster
     * @param ?Event $event
     * @param int $killCount
     * @return void
     */
    private function attemptMythicRewardsForKillCountCappedToOne(Character $character, Monster $monster, ?Event $event, int $killCount): void
    {
        $this->attemptItemRewardsForKillCount($character, $monster, true, $event, $killCount, true);
    }

    /**
     * Attempt an item reward once per kill in the batch, stopping early once the Character's inventory is full.
     *
     * @param Character $character
     * @param Monster $monster
     * @param bool $isMythic
     * @param ?Event $event
     * @param int $killCount
     * @param bool $capToOneReward
     * @return void
     *
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
     * Roll the drop chance for a single item reward attempt and reward the Character if it passes.
     *
     * @param Character $character
     * @param Monster $monster
     * @param bool $isMythic
     * @param ?Event $event
     * @return bool
     *
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
            $maxRoll = intdiv($maxRoll, 2);
            $maximumChance = 0.45;
        }

        $lootingChance = min($lootingChance + ($monster->drop_check * 0.25), $maximumChance);

        if (! $this->chanceCalculator->passesPercentage((2 / $maxRoll) * 100, $lootingChance * 100)) {
            return false;
        }

        if ($character->isInventoryFull()) {
            return false;
        }

        return $this->rewardForCharacter($character, $isMythic);
    }

    /**
     * Reward the Character with a randomly generated Purgatory Chains item, Legendary or Mythic.
     *
     * @param Character $character
     * @param bool $isMythic
     * @return bool
     *
     * @throws Exception
     */
    private function rewardForCharacter(Character $character, bool $isMythic = false): bool
    {
        $item = Item::where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS->value)
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
            $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixTier::MYTHIC->value);

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

            ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found something MYTHICAL in the basement child: '.$item->affix_name, $slot->id);

            return true;
        }

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)
            ->setPaidAmount(RandomAffixTier::LEGENDARY->value);

        $newItem = $item->duplicate();

        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ]);

        $slot = $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $newItem->id,
        ]);

        ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found something LEGENDARY in the basement child: '.$item->affix_name, $slot->id);

        return true;
    }

    /**
     * Roll for and immediately create the Purgatory Smith House global Event, if one is not already active.
     *
     * @param int $killCount
     * @return void
     */
    private function createPossibleEvent(int $killCount = 1): void
    {
        if (Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists()) {
            return;
        }

        $chancePercent = 10 + $killCount;

        if ($this->chanceCalculator->passesPercentage($chancePercent + 1)) {
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

    /**
     * Build a plan that applies no reward, recording why the Location did not qualify.
     *
     * @param Character $character
     * @param Monster $monster
     * @param int $killCount
     * @param array $context
     * @param string $reason
     * @return array
     */
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

    /**
     * Plan the currency amounts to award for the Purgatory Smith House reward.
     *
     * @param Character $character
     * @param ?Event $event
     * @param int $killCount
     * @return array
     */
    private function planCurrencyReward(Character $character, ?Event $event, int $killCount): array
    {
        $maximumAmount = is_null($event) ? 750 : 3_750;
        $amounts = [
            'gold_dust' => $this->randomNumberGenerator->numberBetween(1, $maximumAmount) * $killCount,
            'shards' => $this->randomNumberGenerator->numberBetween(1, $maximumAmount) * $killCount,
            'copper_coins' => 0,
        ];

        $hasItemForCopperCoins = $character->inventory->slots->where('item.effect', ItemEffectType::GET_COPPER_COINS->value)->count() > 0;

        if ($hasItemForCopperCoins) {
            $amounts['copper_coins'] = $this->randomNumberGenerator->numberBetween(1, $maximumAmount) * $killCount;
        }

        return $this->currencyPlanFromAmounts($character, $amounts);
    }

    /**
     * Build the starting and capped target currency amounts for a planned currency reward.
     *
     * @param Character $character
     * @param array $amounts
     * @return array
     */
    private function currencyPlanFromAmounts(Character $character, array $amounts): array
    {
        $maximums = [
            'gold' => CurrencyLimit::MAX_GOLD,
            'gold_dust' => CurrencyLimit::MAX_GOLD_DUST,
            'shards' => CurrencyLimit::MAX_SHARDS,
            'copper_coins' => CurrencyLimit::MAX_COPPER,
        ];
        $starting = [];
        $target = [];

        foreach ($amounts as $currency => $amount) {
            $starting[$currency] = $character->getAttribute($currency);
            $target[$currency] = min($maximums[$currency], $starting[$currency] + $amount);
        }

        return [
            'amounts' => $amounts,
            'starting' => $starting,
            'target' => $target,
        ];
    }

    /**
     * Apply a previously planned currency reward to the Character, up to its capped target amounts.
     *
     * @param Character $character
     * @param array $currencyPlan
     * @return array
     */
    private function applyPlannedCurrencies(Character $character, array $currencyPlan): array
    {
        $applied = [];
        $updates = [];

        foreach (($currencyPlan['amounts'] ?? []) as $currency => $amount) {
            if ($amount <= 0) {
                continue;
            }

            $current = $character->getAttribute($currency);
            $target = $currencyPlan['target'][$currency] ?? $current;

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
            $this->battleMessageHandler->handleCurrencyGainMessage($character->user, CurrenciesMessageTypes::from($currency), $amount, $character->getAttribute($currency));
        }

        return $applied;
    }

    /**
     * Plan the item rewards to award across the batch of kills, bounded by remaining inventory slots.
     *
     * @param Character $character
     * @param Monster $monster
     * @param bool $isMythic
     * @param ?Event $event
     * @param int $killCount
     * @param bool $capToOneReward
     * @return array
     */
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

    /**
     * Roll the drop chance for a single planned item reward attempt.
     *
     * @param Character $character
     * @param Monster $monster
     * @param bool $isMythic
     * @param ?Event $event
     * @return ?array
     */
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
            $maxRoll = intdiv($maxRoll, 2);
            $maximumChance = 0.45;
        }

        $lootingChance = min($lootingChance + ($monster->drop_check * 0.25), $maximumChance);

        if (! $this->chanceCalculator->passesPercentage((2 / $maxRoll) * 100, $lootingChance * 100)) {
            return null;
        }

        return $this->planItemReward($character, $isMythic);
    }

    /**
     * Plan a randomly generated Purgatory Chains item reward for the Character.
     *
     * @param Character $character
     * @param bool $isMythic
     * @return ?array
     */
    private function planItemReward(Character $character, bool $isMythic): ?array
    {
        $item = Item::where('specialty_type', ItemSpecialtyType::PURGATORY_CHAINS->value)
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
            ->setPaidAmount($isMythic ? RandomAffixTier::MYTHIC->value : RandomAffixTier::LEGENDARY->value);
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
                ? 'You found something MYTHICAL in the basement child: '.$item->affix_name
                : 'You found something LEGENDARY in the basement child: '.$item->affix_name,
        ];
    }

    /**
     * Apply the planned item rewards to the Character's inventory, skipping any already applied.
     *
     * @param Character $character
     * @param array $items
     * @return int
     */
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

    /**
     * Plan whether the Purgatory Smith House global Event should be created.
     *
     * @param int $killCount
     * @return array
     */
    private function planPossibleEvent(int $killCount): array
    {
        if (Event::where('type', EventType::PURGATORY_SMITH_HOUSE)->exists()) {
            return ['create' => false, 'type' => EventType::PURGATORY_SMITH_HOUSE, 'reason' => 'active_event_exists'];
        }

        $chancePercent = 10 + $killCount;

        return [
            'create' => $this->chanceCalculator->passesPercentage($chancePercent + 1),
            'type' => EventType::PURGATORY_SMITH_HOUSE,
            'announcement' => 'purgatory_house',
            'message' => 'The floor boards creak and the cries of the children trapped in their own misery wale across the lands. '.
                '"Children of Tlessa, hear me as I lay bare my treasures for you to find in the depths of my own memories." echoes a familiar voice. '.
                'You recognise it. The Creator ...',
        ];
    }

    /**
     * Apply a previously planned Purgatory Smith House global Event, if it plans to be created and none is already active.
     *
     * @param array $eventPlan
     * @return bool
     */
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
