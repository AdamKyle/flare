<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ClearAndRebuildCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear-and-rebuild:cache';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clears the system cache and rebuilds the character attach data.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct() {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        Artisan::call('cache:clear');
        Artisan::call('create:character-attack-data');
        Artisan::call('update:kingdom');
        Artisan::call('delete-duplicate:quest_items');
        Artisan::call('de-level:characters');
    }
}
