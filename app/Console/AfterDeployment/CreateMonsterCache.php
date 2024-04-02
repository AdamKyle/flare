<?php

namespace App\Console\AfterDeployment;

use App\Flare\Services\BuildMonsterCacheService;
use Illuminate\Console\Command;
use Psr\SimpleCache\InvalidArgumentException;

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
     * @throws InvalidArgumentException
     */
    public function handle(BuildMonsterCacheService $buildMonsterCacheService): void
    {
        $buildMonsterCacheService->buildCache();
        $buildMonsterCacheService->buildCelesetialCache();
        $buildMonsterCacheService->buildRaidCache();
        $buildMonsterCacheService->buildSpecialLocationMonsterList();
    }
}
