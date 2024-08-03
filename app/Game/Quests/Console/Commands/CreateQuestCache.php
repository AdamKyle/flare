<?php

namespace App\Game\Quests\Console\Commands;

use App\Game\Quests\Services\BuildQuestCacheService;
use Illuminate\Console\Command;

class CreateQuestCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:quest-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Creates the command cache';

    /**
     * Execute the console command.
     */
    public function handle(BuildQuestCacheService $buildQuestCacheService)
    {
        $this->line('Building regular quest cache ...');
        $buildQuestCacheService->buildQuestCache();

        $this->line('Building Raid quest cache ...');
        $buildQuestCacheService->buildRaidQuestCache();

        $this->line('All done :)');
    }
}
