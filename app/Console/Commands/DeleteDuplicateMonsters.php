<?php

namespace App\Console\Commands;

use App\Flare\Models\Monster;
use App\Flare\Services\BuildMonsterCacheService;
use Illuminate\Console\Command;

class DeleteDuplicateMonsters extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:duplicate-monsters';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes Duplicate Monsters';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        Monster::where('name', 'like', '%DUPLICATE%')->delete();

        resolve(BuildMonsterCacheService::class)->buildCache();
    }
}
