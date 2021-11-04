<?php

namespace App\Console\Commands;

use App\Flare\Models\Character;
use App\Flare\Models\Item;
use File;
use Illuminate\Console\Command;

class MoveInfoFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'move:files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move info files';

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
     * @return mixed
     */
    public function handle()
    {
        File::deleteDirectory(storage_path('app/public/info'));
        File::copyDirectory(resource_path('info'), storage_path('app/public/info'));
    }
}