<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\WeeklyMonsterFight;
use App\Game\BattleRewardProcessing\Exceptions\WeeklyRewardInventoryFullException;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Core\Items\Values\RandomAffixTier;
use App\Game\Maps\Values\MapName;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use App\Game\Skills\Contracts\SkillBonusQuery;
use Illuminate\Support\Facades\Log;
use Throwable;

class LocationSpecialtyHandler
{
    use FetchEquipped;

    /**
     * @param RandomAffixGenerator $randomAffixGenerator
     * @param RandomNumberGenerator $randomNumberGenerator
     * @param ChanceCalculator $chanceCalculator
     * @param SkillBonusQuery $skillBonusQuery
     */
    public function __construct(
        private readonly RandomAffixGenerator $randomAffixGenerator,
        private readonly RandomNumberGenerator $randomNumberGenerator,
        private readonly ChanceCalculator $chanceCalculator,
        private readonly SkillBonusQuery $skillBonusQuery,
    ) {}

    /**
     * Reward the Character for defeating a weekly special location monster, scaled by their Looting bonus and deaths.
     *
     * @param Character $character
     * @param WeeklyMonsterFight $weeklyMonsterFight
     * @param bool $mainItemIsCosmic
     * @return void
     *
     * @throws WeeklyRewardInventoryFullException
     */
    public function handleMonsterFromSpecialLocation(Character $character, WeeklyMonsterFight $weeklyMonsterFight, bool $mainItemIsCosmic = true): void
    {
        $availableSlots = max(0, $character->inventory_max - $character->inventory->slots()->count());

        if ($availableSlots < 4) {
            throw new WeeklyRewardInventoryFullException('Weekly reward delivery requires four available inventory slots.');
        }

        $lootingDropChance = $this->skillBonusQuery->skillBonus($character->skills->where('baseSkill.name', '=', 'Looting')->first());

        $lootingDropChance = min($lootingDropChance, 0.15);

        if ($weeklyMonsterFight->character_deaths > 0) {
            $reduction = 0.02 * $weeklyMonsterFight->character_deaths;

            $lootingDropChance = $lootingDropChance - $reduction;
        }

        $chance = 0.01 + ($lootingDropChance < 0 ? 0 : $lootingDropChance);

        if ($this->chanceCalculator->passesPercentage(2.0, $chance * 100)) {
            $this->giveItemReward($character, $mainItemIsCosmic);
        }

        for ($i = 1; $i <= 3; $i++) {
            $character = $this->handOverAward($character, false, ! $mainItemIsCosmic);
        }
    }

    /**
     * Hand the Character the main weekly reward item and announce it globally.
     *
     * @param Character $character
     * @param bool $isCosmic
     * @return void
     *
     * @throws WeeklyRewardInventoryFullException
     */
    private function giveItemReward(Character $character, bool $isCosmic = true): void
    {
        $character = $this->handOverAward($character, $isCosmic);

        $this->dispatchAfterReward(
            new GlobalMessageEvent($character->name.' Has slaughtered a beast beyond comprehension and been rewarded with an interesting gift!'),
            $character,
            'global',
        );
    }

    /**
     * Add a random reward item to the Character's inventory and tell them what they received.
     *
     * @param Character $character
     * @param bool $isCosmic
     * @param bool $secondaryIsLegendary
     * @return Character
     *
     * @throws WeeklyRewardInventoryFullException
     */
    private function handOverAward(Character $character, bool $isCosmic = true, bool $secondaryIsLegendary = false): Character
    {
        if ($character->isInventoryFull()) {
            throw new WeeklyRewardInventoryFullException('Weekly reward delivery inventory became full.');
        }

        $item = $this->giveCharacterRandomItem($character, $isCosmic, $secondaryIsLegendary);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->where('item_id', '=', $item->id)->first();

        $message = 'You have received a '.$this->rewardRarityName($isCosmic, $secondaryIsLegendary).' item! How exciting! Rewarded with: '.$slot->item->affix_name;

        $this->dispatchAfterReward(new ServerMessageEvent($character->user, $message, $slot->id), $character, 'player');

        return $character->refresh();
    }

    /**
     * Return the rarity name announced for a reward item.
     *
     * @param bool $isCosmic
     * @param bool $secondaryIsLegendary
     * @return string
     */
    private function rewardRarityName(bool $isCosmic, bool $secondaryIsLegendary): string
    {
        if ($secondaryIsLegendary) {
            return 'Legendary';
        }

        if ($isCosmic) {
            return 'Cosmic';
        }

        return 'Mythical';
    }

    /**
     * Return the random affix tier paid for a reward item.
     *
     * @param bool $isCosmic
     * @param bool $secondaryIsLegendary
     * @return int
     */
    private function rewardAffixTier(bool $isCosmic, bool $secondaryIsLegendary): int
    {
        if ($secondaryIsLegendary) {
            return RandomAffixTier::LEGENDARY->value;
        }

        if ($isCosmic) {
            return RandomAffixTier::COSMIC->value;
        }

        return RandomAffixTier::MYTHIC->value;
    }

    /**
     * Dispatch a reward broadcast, logging instead of failing when the broadcast cannot be sent.
     *
     * @param object $event
     * @param Character $character
     * @param string $broadcastType
     * @return void
     */
    private function dispatchAfterReward(object $event, Character $character, string $broadcastType): void
    {
        try {
            event($event);
        } catch (Throwable $throwable) {
            Log::channel('reward_processing')->error('Weekly reward broadcast failed after item delivery.', [
                'character_id' => $character->id,
                'broadcast_type' => $broadcastType,
                'exception' => $throwable,
            ]);
        }
    }

    /**
     * Duplicate a random base item of the rolled specialty type and enchant it at the reward's rarity.
     *
     * @param Character $character
     * @param bool $isCosmic
     * @param bool $secondaryIsLegendary
     * @return Item
     */
    private function giveCharacterRandomItem(Character $character, bool $isCosmic = true, bool $secondaryIsLegendary = false): Item
    {
        $typeOfItem = $this->getType($character, $isCosmic);

        $item = Item::where('specialty_type', $typeOfItem)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount($this->rewardAffixTier($isCosmic, $secondaryIsLegendary));

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if ($this->chanceCalculator->passesPercentage(50.0)) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id,
            ]);
        }

        if ($secondaryIsLegendary) {
            return $duplicateItem->refresh();
        }

        if ($isCosmic) {
            $duplicateItem->update(['is_cosmic' => true]);

            return $duplicateItem->refresh();
        }

        $duplicateItem->update(['is_mythic' => true]);

        return $duplicateItem->refresh();
    }

    /**
     * Roll the specialty item type for the Character's map, improved by the map's specialty gear they wear.
     *
     * @param Character $character
     * @param bool $isCosmicItem
     * @return string|null
     */
    private function getType(Character $character, bool $isCosmicItem): ?string
    {
        $chance = $isCosmicItem ? 100 : $this->randomNumberGenerator->numberBetween(1, 100);

        $equippedItems = $this->fetchEquipped($character) ?? collect();
        $equippedChance = 0.01;

        $totalEquippedChance = match ($character->map->gameMap->name) {
            MapName::HELL->value => $equippedChance * $equippedItems->whereNull('item.specialty_type')->where('item.skill_level_required', 400)->count(),
            MapName::DELUSIONAL_MEMORIES->value => $equippedChance * $equippedItems->where('item.specialty_type', ItemSpecialtyType::PURGATORY_CHAINS->value)->count(),
            MapName::TWISTED_MEMORIES->value => $equippedChance * $equippedItems->where('item.specialty_type', ItemSpecialtyType::TWISTED_EARTH->value)->count(),
            default => 0.0
        };

        if ($chance + $totalEquippedChance < 80) {
            return null;
        }

        return match ($character->map->gameMap->name) {
            MapName::HELL->value => ItemSpecialtyType::HELL_FORGED->value,
            MapName::DELUSIONAL_MEMORIES->value => ItemSpecialtyType::DELUSIONAL_SILVER->value,
            MapName::TWISTED_MEMORIES->value => ItemSpecialtyType::FAITHLESS_PLATE->value,
            default => null
        };
    }
}
