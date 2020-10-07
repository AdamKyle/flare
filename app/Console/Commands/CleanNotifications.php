<?php

namespace App\Console\Commands;

use App\Flare\Models\Item;
use App\Flare\Models\Notification;
use Illuminate\Console\Command;

class CleanNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans read notifications, 100 at a time';

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
        Notification::where('read', true)->chunk(100, function($notifications) {
            foreach($notifications as $notification) {
                $notification->delete();
            }
        });

        $this->info('cleaned');
    }
}
