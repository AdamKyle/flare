<?php

namespace App\Console\Commands;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

class FlagUsersForDeletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'flag:users-for-deletion';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Flags users who have not logged in for a long time for deletion.';

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
     * @return void
     */
    public function handle(): void {
        $users = User::whereNull('last_logged_in')
                     ->orWhereDate('last_logged_in', '<', now()->subMonths(5))
                     ->where('will_be_deleted', false)
                     ->get();

        $progressBar = new ProgressBar(new ConsoleOutput(), $users->count());

        foreach($users as $user) {
            $user->update([
                'will_be_deleted' => true,
            ]);

            $progressBar->advance();
        }

        $progressBar->finish();
    }
}
