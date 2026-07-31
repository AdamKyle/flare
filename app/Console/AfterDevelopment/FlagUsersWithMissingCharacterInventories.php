<?php

namespace App\Console\AfterDevelopment;

use App\Flare\Models\User;
use Illuminate\Console\Command;

class FlagUsersWithMissingCharacterInventories extends Command
{
    protected $signature = 'flag:users-with-missing-character-inventories {--dry-run}';

    protected $description = 'Mark users whose characters have no inventory for account deletion.';

    public function handle(): int
    {
        $query = User::query()
            ->whereHas('character')
            ->whereDoesntHave('character.inventory')
            ->where('will_be_deleted', false);
        $count = (clone $query)->count();

        if ($this->option('dry-run')) {
            $this->info($count.' users would be marked for deletion.');

            return self::SUCCESS;
        }

        $query->update(['will_be_deleted' => true]);
        $this->info($count.' users were marked for deletion.');

        return self::SUCCESS;
    }
}
