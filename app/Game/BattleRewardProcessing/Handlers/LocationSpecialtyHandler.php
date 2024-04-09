<?php

namespace App\Game\BattleRewardProcessing\Handlers;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\GlobalEventGoal;
use App\Flare\Models\Item;
use App\Flare\Models\Monster;
use App\Flare\Models\WeeklyMonsterFight;
use App\Flare\Values\ItemSpecialtyType;
use App\Flare\Values\RandomAffixDetails;
use App\Game\Messages\Events\GlobalMessageEvent;
use App\Game\Messages\Events\ServerMessageEvent;
use Facades\App\Flare\Calculators\DropCheckCalculator;

class LocationSpecialtyHandler {

    private RandomAffixGenerator $randomAffixGenerator;

    public function __construct(RandomAffixGenerator $randomAffixGenerator) {
        $this->randomAffixGenerator = $randomAffixGenerator;
    }

    public function handleMonsterFromSpecialLocation(Character $character, WeeklyMonsterFight $weeklyMonsterFight): void {

        $lootingDropChance = $character->skills->where('baseSkill.name', '=', 'Looting')->first()->skill_bonus;

        if ($weeklyMonsterFight->character_deaths > 0) {
            $reduction = 0.02 * $weeklyMonsterFight->character_deaths;

            $lootingDropChance = $lootingDropChance - $reduction;
        }

        $chance = 0.01 + ($lootingDropChance < 0 ? 0 : $lootingDropChance);

        if (DropCheckCalculator::fetchDifficultItemChance($chance, 100)) {
            $this->giveItemReward($character);
        }

        for($i = 1; $i <= 3; $i++) {
            $character = $this->handOverAward($character, false);
        }
    }

    private function giveItemReward(Character $character): void {
        $character = $this->handOverAward($character);

        event(new GlobalMessageEvent($character->name . ' Has slaughtered a beast beyond comprehension and been rewarded with a cosmic gift!'));
    }

    private function handOverAward(Character $character, bool $isCosmic = true): Character {
        $item = $this->giveCharacterRandomItem($character, $isCosmic);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        $slot = $character->inventory->slots->where('item_id', '=', $item->id)->first();

        if ($isCosmic) {
            event(new ServerMessageEvent($character->user, 'You have received a cosmic item! How exciting! Rewarded with: ' . $slot->item->affix_name, $slot->id));
        } else {
            event(new ServerMessageEvent($character->user, 'You have received a mythical item! How exciting! Rewarded with: ' . $slot->item->affix_name, $slot->id));
        }

        return $character->refresh();
    }

    private function giveCharacterRandomItem(Character $character, bool $isCosmic = true): Item {
        $item = Item::where('specialty_type', ItemSpecialtyType::DELUSIONAL_SILVER)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();


        $randomAffix = $this->randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount(RandomAffixDetails::COSMIC);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        // @codeCoverageIgnoreStart
        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id
            ]);
        }
        // @codeCoverageIgnoreEnd

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
}
