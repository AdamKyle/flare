<?php

namespace App\Console\Commands;

use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\User;
use Illuminate\Console\Command;

class DeleteFlaggedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:flagged-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete the flagged users';

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
        User::where('will_be_deleted', true)->chunkById(100, function($users) {
            foreach ($users as $user) {
                AccountDeletionJob::dispatch($user, true)->onConnection('long_running');
            }
        });
    }
}
