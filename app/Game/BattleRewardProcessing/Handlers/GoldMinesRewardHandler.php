<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Items\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Event;
use App\Flare\Models\Item;
use App\Flare\Models\Location;
use App\Flare\Models\Monster;
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

class GoldMinesRewardHandler
{
    private array $earnedCurrencies = [];

    public function __construct(private RandomAffixGenerator $randomAffixGenerator, private BattleMessageHandler $battleMessageHandler) {}

    public function getEarnedCurrencies(): array
    {
        return $this->earnedCurrencies;
    }

    public function handleFightingAtGoldMines(Character $character, Monster $monster, int $killCount = 1): Character
    {
        $this->earnedCurrencies = [];
        $plan = $this->planFightingAtGoldMines($character, $monster, $killCount);

        if (! $plan['applies']) {
            return $character;
        }

        $this->applyPlannedGoldMinesReward($character, $plan);

        return $character->refresh();
    }

    public function planFightingAtGoldMines(Character $character, Monster $monster, int $killCount = 1, array $context = []): array
    {
        $location = Location::where('x', $character->map->character_position_x)
            ->where('y', $character->map->character_position_y)
            ->where('game_map_id', $character->map->game_map_id)
            ->first();

        if (is_null($location) || is_null($location->locationType())) {
            return $this->noopPlan($character, $monster, $killCount, $context, 'missing_location');
        }

        if (! $location->locationType()->isGoldMines()) {
            return $this->noopPlan($character, $monster, $killCount, $context, 'not_gold_mines');
        }

        $event = Event::where('type', EventType::GOLD_MINES)->first();
        $currencyPlan = $this->planCurrencyReward($character, $event, $killCount);
        $itemPlans = [];
        $shouldAttemptItems = $character->currentAutomations->isEmpty() && $this->isMonsterAtLeastHalfWayOrMore($location, $monster);

        if ($shouldAttemptItems) {
            $itemPlans = $this->planItemRewards($character, $monster, $event, $killCount);
        }

        return [
            'handler' => 'gold_mines',
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
            'event' => $shouldAttemptItems ? $this->planPossibleEvent($killCount) : ['create' => false],
            'currencies' => $currencyPlan,
            'items' => $itemPlans,
        ];
    }

    public function applyPlannedGoldMinesReward(Character $character, array $plan): array
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
     * Reward the character with currencies.
     *
     * - Only gives copper coins if the character has
     */
    public function currencyReward(Character $character, ?Event $event = null, int $killCount = 1): Character
    {
        $this->earnedCurrencies = $this->applyPlannedCurrencies($character, $this->planCurrencyReward($character, $event, $killCount));

        return $character->refresh();
    }

    /**
     * Handle item Reward for player.
     *
     * @throws Exception
     */
    protected function handleItemReward(Character $character, Monster $monster, ?Event $event = null, int $killCount = 1): Character
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = 1_000;
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

        for ($iterationIndex = 0; $iterationIndex < $killCount; $iterationIndex++) {
            if ($character->isInventoryFull()) {
                break;
            }

            if (! DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
                continue;
            }

            $this->rewardForCharacter($character);
            $character = $character->refresh();
        }

        $this->createPossibleEvent($killCount);

        return $character->refresh();
    }

    /**
     * Reward player with item.
     *
     *
     * @throws Exception
     */
    protected function rewardForCharacter(Character $character, bool $isMythic = false): void
    {
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

        if (! $isMythic) {
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

            ServerMessageHandler::sendBasicMessageWithId($character->user, 'You found something unique, in the mines child: '.$item->affix_name, $slot->id);
        }
    }

    private function noopPlan(Character $character, Monster $monster, int $killCount, array $context, string $reason): array
    {
        return [
            'handler' => 'gold_mines',
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
        $maximumAmount = is_null($event) ? 375 : 750;
        $maximumGold = is_null($event) ? 750 : 3750;
        $amounts = [
            'gold' => RandomNumberGenerator::generateRandomNumber(1, $maximumGold) * $killCount,
            'gold_dust' => RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount,
            'shards' => RandomNumberGenerator::generateRandomNumber(1, $maximumAmount) * $killCount,
        ];

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

    private function planItemRewards(Character $character, Monster $monster, ?Event $event, int $killCount): array
    {
        $lootingChance = $character->skills->where('baseSkill.name', 'Looting')->first()->skill_bonus;
        $maxRoll = 1_000;
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
        $items = [];
        $remainingSlots = max(0, $character->inventory_max - $character->getInventoryCount());

        for ($iterationIndex = 0; $iterationIndex < $killCount && count($items) < $remainingSlots; $iterationIndex++) {
            if (! DropCheckCalculator::fetchDifficultItemChance($lootingChance, $maxRoll)) {
                continue;
            }

            $itemPlan = $this->planItemReward($character);

            if (! is_null($itemPlan)) {
                $items[] = $itemPlan;
            }
        }

        return $items;
    }

    private function planItemReward(Character $character): ?array
    {
        $item = Item::whereNull('specialty_type')
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereDoesntHave('appliedHolyStacks')
            ->whereNotIn('type', ['alchemy', 'artifact', 'trinket', 'quest'])
            ->inRandomOrder()
            ->first();

        if (is_null($item)) {
            return null;
        }

        $randomAffixGenerator = $this->randomAffixGenerator->setCharacter($character)->setPaidAmount(RandomAffixDetails::LEGENDARY);
        $newItem = $item->duplicate();
        $newItem->update([
            'item_prefix_id' => $randomAffixGenerator->generateAffix('prefix')->id,
            'item_suffix_id' => $randomAffixGenerator->generateAffix('suffix')->id,
        ]);

        return [
            'base_item_id' => $item->id,
            'planned_item_id' => $newItem->id,
            'is_mythic' => false,
            'message' => 'You found something unique, in the mines child: '.$item->affix_name,
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
        if (Event::where('type', EventType::GOLD_MINES)->exists()) {
            return ['create' => false, 'type' => EventType::GOLD_MINES, 'reason' => 'active_event_exists'];
        }

        $chancePercent = 10 + $killCount;
        $threshold = 100 - $chancePercent;

        return [
            'create' => RandomNumberGenerator::generateTrueRandomNumber(100) >= $threshold,
            'type' => EventType::GOLD_MINES,
            'announcement' => 'gold_mines',
            'message' => 'There comes a howling scream from the depths of the mines in the land of tormenting shadows.
                Someone released the vien of hate and flooded the mines with treasure!',
        ];
    }

    private function applyPlannedEvent(array $eventPlan): bool
    {
        if (! ($eventPlan['create'] ?? false)) {
            return false;
        }

        if (Event::where('type', EventType::GOLD_MINES)->exists()) {
            return false;
        }

        Event::create([
            'type' => EventType::GOLD_MINES,
            'started_at' => now(),
            'ends_at' => now()->addHour(),
        ]);

        AnnouncementHandler::createAnnouncement($eventPlan['announcement']);
        event(new GlobalMessageEvent($eventPlan['message']));

        return true;
    }

    /**
     * 1 out of 1 million chance to create an event.
     */
    protected function createPossibleEvent(int $killCount = 1): void
    {
        if (Event::where('type', EventType::GOLD_MINES)->exists()) {
            return;
        }

        $chancePercent = 10 + $killCount;
        $threshold = 100 - $chancePercent;

        if (RandomNumberGenerator::generateTrueRandomNumber(100) >= $threshold) {
            Event::create([
                'type' => EventType::GOLD_MINES,
                'started_at' => now(),
                'ends_at' => now()->addHour(),
            ]);

            AnnouncementHandler::createAnnouncement('gold_mines');

            event(new GlobalMessageEvent(
                'There comes a howling scream from the depths of the mines in the land of tormenting shadows.
                Someone released the vien of hate and flooded the mines with treasure!'
            ));
        }
    }
}
