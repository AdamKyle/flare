<?php

namespace App\Console\Commands;

use App\Flare\Services\BuildMonsterCacheService;
use Illuminate\Console\Command;

class CreateMonsterCache extends Command {

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:monster-cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates monsters to fight';

    /**
     * Execute the console command.
     *
     * @param BuildMonsterCacheService $buildMonsterCacheService
     * @return void
     */
    public function handle(BuildMonsterCacheService $buildMonsterCacheService): void
    {
        $buildMonsterCacheService->buildCache();
        $buildMonsterCacheService->buildCelesetialCache();
        $buildMonsterCacheService->createRankMonsters();
    }
}
