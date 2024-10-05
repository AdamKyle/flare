<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Models\WeeklyMonsterFight;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\MapNameValue;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Character\Concerns\FetchEquipped;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class LocationSpecialtyHandler
{

    use FetchEquipped;

    private RandomAffixGenerator $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator)
    {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    public function handleMonsterFromSpecialLocation(Character $character, WeeklyMonsterFight $weeklyMonsterFight, bool $mainItemIsCosmic = true): void
    {

        $lootingDropChance = $character->skills->where('baseSkill.name', '=', 'Looting')->first()->skill_bonus;

        $lootingDropChance = min($lootingDropChance, 0.15);

        if ($weeklyMonsterFight->character_deaths > 0) {
            $reduction = 0.02 * $weeklyMonsterFight->character_deaths;

            $lootingDropChance = $lootingDropChance - $reduction;
        }

        $chance = 0.01 + ($lootingDropChance < 0 ? 0 : $lootingDropChance);

        if (DropCheckCalculator::fetchDifficultItemChance($chance, 100)) {
            $this->giveItemReward($character, $mainItemIsCosmic);
        }

        for ($i = 1; $i <= 3; $i++) {
            $character = $this->handOverAward($character, false, !$mainItemIsCosmic);
        }
    }

    private function giveItemReward(Character $character, $isCosmic = true): void
    {
        $character = $this->handOverAward($character, $isCosmic);

        event(new GlobalMessageEvent($character->name.' Has slaughtered a beast beyond comprehension and been rewarded with a cosmic gift!'));
    }

    private function handOverAward(Character $character, bool $isCosmic = true, bool $secondaryIsLegendary = false): Character
    {
        $item = $this->giveCharacterRandomItem($character, $isCosmic, $secondaryIsLegendary);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        $character = $character->refresh();

        $slot = $character->inventory->slots->where('item_id', '=', $item->id)->first();

        if ($secondaryIsLegendary) {
            event(new ServerMessageEvent($character->user, 'You have received a Legendary item! How exciting! Rewarded with: '.$slot->item->affix_name, $slot->id));
        } elseif ($isCosmic) {
            event(new ServerMessageEvent($character->user, 'You have received a Cosmic item! How exciting! Rewarded with: '.$slot->item->affix_name, $slot->id));
        } else {
            event(new ServerMessageEvent($character->user, 'You have received a Mythical item! How exciting! Rewarded with: '.$slot->item->affix_name, $slot->id));
        }

        return $character->refresh();
    }

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
            ->setPaidAmount(($secondaryIsLegendary ? RandomAffixDetails::LEGENDARY : ($isCosmic ? RandomAffixDetails::COSMIC : RandomAffixDetails::MYTHIC)));

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        // @codeCoverageIgnoreStart
        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id,
            ]);
        }
        // @codeCoverageIgnoreEnd

        if ($secondaryIsLegendary) {
            return $duplicateItem->refresh();
        }

        if ($isCosmic) {
            $duplicateItem->update([
                'is_cosmic' => true,
            ]);
        } else {
            $duplicateItem->update([
                'is_mythic' => true,
            ]);
        }

        return $duplicateItem->refresh();
    }

    private function getType(Character $character, bool $isCosmicItem): ?string {

        $typeOfItem = null;

        $chance = $isCosmicItem ? 100 : rand(1, 100);

        $equippedItems = $this->fetchEquipped($character) ?? collect();
        $equippedChance = 0.01;

        $chance += match($character->map->gameMap->mapType()) {
            MapNameValue::HELL => $equippedChance * $equippedItems->whereNull('item.specialty_type')->where('item.skill_level_required', 400)->sum(),
            MapNameValue::DELUSIONAL_MEMORIES => $equippedChance * $equippedItems->where('item.specialty_type', ItemSpecialtyType::PURGATORY_CHAINS)->sum(),
            MapNameValue::TWISTED_MEMORIES => $equippedChance * $equippedItems->where('item.specialty_type', ItemSpecialtyType::TWISTED_EARTH)->sum(),
            default => 0.0
        };

        if ($chance >= 80) {
            $typeOfItem = match($character->map->gameMap->mapType()) {
                MapNameValue::HELL => ItemSpecialtyType::HELL_FORGED,
                MapNameValue::DELUSIONAL_MEMORIES => ItemSpecialtyType::DELUSIONAL_SILVER,
                MapNameValue::TWISTED_MEMORIES => ItemSpecialtyType::FAITHLESS_PLATE,
                default => null
            };
        }

        return $typeOfItem;
    }
}
