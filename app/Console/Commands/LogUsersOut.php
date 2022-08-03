<?php

namespace App\Console\Commands;

use App\Admin\Events\RefreshUserScreenEvent;
use App\Flare\Models\Character;
use App\Flare\Models\Session;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class LogUsersOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'log-out:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Logs users out of the system.';

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
        Session::truncate();

        Character::chunkById(100, function($characters) {
            foreach ($characters as $character) {
                event(new RefreshUserScreenEvent($character->user));
            }
        });
    }
}
