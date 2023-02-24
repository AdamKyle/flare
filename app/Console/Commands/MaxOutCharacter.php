<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Core\Values\FactionType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MaxOutCharacter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'max-out:character {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Max out a character';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void {
        $characterName = $this->argument('name');

        $character = Character::where('name', $characterName)->first();

        if (is_null($character)) {
            $this->error('No character found for name: ' . $characterName);

            return;
        }

        $items = $this->findItemsToGive();

        if (empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $character = $this->giveQuestItemToPlayer($character, $item);
        }

        $character = $this->levelSkills($character);
        $character = $this->levelFactions($character);
        $character = $this->levelMercenaries($character);
        $character = $this->maxOutClassRanks($character);

        $character->update([
            'gold'         => MaxCurrenciesValue::MAX_GOLD,
            'gold_dust'    => MaxCurrenciesValue::MAX_GOLD_DUST,
            'shards'       => MaxCurrenciesValue::MAX_SHARDS,
            'copper_coins' => MaxCurrenciesValue::MAX_COPPER
        ]);

        $character = $character->refresh();

        Artisan::call('level:character ' . $character->id . ' ' . 4999);
    }

    protected function findItemsToGive(): array {
        $items = [];

        $itemNames = [
            'Sash of the Heavens',
            'Key of the Labyrinth',
            'Torch',
            'Flask Of Fresh Air',
            'River Styx Sandals',
            'Life\'s Flail',
            'Crystal Eye Glass',
            'Dead King\'s Crown',
            'Demonic Leather Boots',
            'Purgatory\'s Lantern',
            'Bag of Chance',
            'Satan\'s Heart',
            'Broken Copper Coin',
        ];

        foreach ($itemNames as $itemName) {
            $item = Item::where('name', $itemName)->first();

            if (is_null($item)) {

                $this->error('Could not find item: ' . $itemName);

                return[];
            }

            $items[] = $item;
        }

        return $items;
    }

    protected function giveQuestItemToPlayer(Character $character, Item $item): Character {
        $character->inventory->slots()->create([
            'character_inventory_id' => $character->inventory->id,
            'item_id'                => $item->id,
        ]);

        return $character->refresh();
    }

    protected function levelSkills(Character $character): Character {
        foreach ($character->skills as $skill) {

            if ($skill->baseSkill->type === SkillTypeValue::TRAINING) {
                $skill->update(['level' => 999]);
            }

            if ($skill->baseSkill->type === SkillTypeValue::CRAFTING ||
                $skill->baseSkill->type === SkillTypeValue::DISENCHANTING ||
                $skill->baseSkill->type === SkillTypeValue::ENCHANTING)
            {
                $skill->update(['level' => 400, 'is_hidden' => false]);
            }

            if ($skill->baseSkill->type === SkillTypeValue::ALCHEMY) {
                $skill->update(['level' => 200, 'is_hidden' => false]);
            }
        }

        return $character->refresh();
    }

    protected function levelFactions(Character $character): Character {
        $character->factions()->update([
            'current_level'    => 5,
            'maxed'            => true,
            'title'            => FactionType::MYTHIC_PROTECTOR,
        ]);

        return $character->refresh();
    }

    protected function levelMercenaries(Character $character): Character {
        $character->mercenaries()->update([
            'current_level'      => 100,
            'reincarnated_bonus' => 11,
            'times_reincarnated' => 10,
        ]);

        return $character->refresh();
    }

    protected function maxOutClassRanks(Character $character): Character {
        foreach ($character->classRanks as $rank) {
            $rank->weaponMasteries()->update([
                'level' => 100,
            ]);

            $rank->update([
                'level' => 100,
            ]);
        }

        return $character->refresh();
    }
}
