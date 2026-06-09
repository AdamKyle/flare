<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Item;
use Illuminate\Console\Command;

class CleanInvalidWeapons extends Command
{
    const INVALID_TYPE = 'weapon';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:invalid-weapons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans up the invalid weapons';

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
     */
    public function handle(): void
    {
        Item::where('type', self::INVALID_TYPE)->delete();
    }
}
