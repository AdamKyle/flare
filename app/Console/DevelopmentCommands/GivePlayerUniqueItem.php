<?php

namespace App\Console\DevelopmentCommands;

use App\Flare\Builders\RandomAffixGenerator;
use App\Flare\Models\Character;
use App\Flare\Models\Item;
use App\Flare\Values\RandomAffixDetails;
use Exception;
use Illuminate\Console\Command;

class GivePlayerUniqueItem extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'give:player-unique-item {characterName}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Gives a player a unique item.';

    /**
     * Execute the console command.
     */
    public function handle(RandomAffixGenerator $randomAffixGenerator)
    {

        $character = Character::where('name', $this->argument('characterName'))->first();

        if (is_null($character)) {
            return $this->line('No character for that name found.');
        }

        $type = $this->choice('Which Type', [
            'basic', 'medium', 'legendary',
        ]);

        $cost = match ($type) {
            'basic' => RandomAffixDetails::BASIC,
            'medium' => RandomAffixDetails::MEDIUM,
            'legendary' => RandomAffixDetails::LEGENDARY,
            default => throw new Exception('undefined type for unique')
        };

        $item = $this->getUniqueForPlayer($randomAffixGenerator, $character, $cost);

        $character->inventory->slots()->create([
            'inventory_id' => $character->inventory->id,
            'item_id' => $item->id,
        ]);

        return $this->line('Gave: '.$item->affix_name.' To character.');
    }

    protected function getUniqueForPlayer(RandomAffixGenerator $randomAffixGenerator, Character $character, int $paidAmount): Item
    {
        $item = Item::where('cost', '<=', $paidAmount)
            ->whereNull('item_prefix_id')
            ->whereNull('item_suffix_id')
            ->whereNull('specialty_type')
            ->whereNotIn('type', ['alchemy', 'quest', 'trinket', 'artifact'])
            ->whereDoesntHave('appliedHolyStacks')
            ->inRandomOrder()
            ->first();

        $randomAffix = $randomAffixGenerator
            ->setCharacter($character)
            ->setPaidAmount($paidAmount);

        $duplicateItem = $item->duplicate();

        $duplicateItem->update([
            'item_prefix_id' => $randomAffix->generateAffix('prefix')->id,
        ]);

        if (rand(1, 100) > 50) {
            $duplicateItem->update([
                'item_suffix_id' => $randomAffix->generateAffix('suffix')->id,
            ]);
        }

        return $duplicateItem;
    }
}
