<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\InventorySet;
use Illuminate\Console\Command;

class AddBatchCraftingSetToPlayers extends Command
{
    protected $signature = 'batch-crafting:add-set-to-players {--apply : Apply changes instead of dry-running}';

    protected $description = 'Idempotently adds the Batch Crafting inventory set to existing characters.';

    public function handle(): void
    {
        $scanned = 0;
        $existing = 0;
        $created = 0;
        $renamed = 0;
        $wouldCreate = 0;
        $wouldRename = 0;
        $apply = (bool) $this->option('apply');

        Character::query()
            ->select('id')
            ->orderBy('id')
            ->chunk(100, function ($characters) use (&$scanned, &$existing, &$created, &$renamed, &$wouldCreate, &$wouldRename, $apply) {
                foreach ($characters as $character) {
                    $scanned++;

                    $existingSet = InventorySet::where('character_id', $character->id)
                        ->where('special_type', InventorySet::BATCH_CRAFTING_SPECIAL_TYPE)
                        ->first();

                    if (! is_null($existingSet)) {
                        if ($existingSet->name === InventorySet::BATCH_CRAFTING_SET_NAME) {
                            $existing++;

                            continue;
                        }

                        if (! $apply) {
                            $wouldRename++;

                            continue;
                        }

                        $existingSet->update(['name' => InventorySet::BATCH_CRAFTING_SET_NAME]);
                        $renamed++;

                        continue;
                    }

                    if (! $apply) {
                        $wouldCreate++;

                        continue;
                    }

                    InventorySet::create([
                        'name' => InventorySet::BATCH_CRAFTING_SET_NAME,
                        'character_id' => $character->id,
                        'is_equipped' => false,
                        'can_be_equipped' => false,
                        'special_type' => InventorySet::BATCH_CRAFTING_SPECIAL_TYPE,
                        'max_slots' => InventorySet::BATCH_CRAFTING_MAX_SLOTS,
                    ]);

                    $created++;
                }
            });

        $mode = $apply ? 'apply' : 'dry-run';

        $this->line("mode: {$mode}");
        $this->line("scanned: {$scanned}");
        $this->line("existing: {$existing}");
        $this->line("created: {$created}");
        $this->line("renamed: {$renamed}");
        $this->line("dry-run-would-create: {$wouldCreate}");
        $this->line("dry-run-would-rename: {$wouldRename}");
    }
}
