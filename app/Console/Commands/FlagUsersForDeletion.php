<?php

namespace App\Console\Commands;

use App\Flare\Mail\GenericMail;
use App\Flare\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

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
        $users = User::whereDate('will_be_deleted', '<', now()->subMonths(5))->orWhereNull('last_logged_in');

        $users->chunkById(100, function($users) {
           foreach ($users as $user) {
               $user->update([
                   'will_be_deleted' => true,
               ]);

               $accountDeletionMessages = 'Your account has not been logged into for a while. As a result, next month your account will be deleted. 
               Do not worry, you can always come back and create anew account, log into day to prevent this, or log in and delete your account your self. 
               If the system deletes your account, you will receive one more email next month to confirm this action was done.';

               // Mail::to($user->email)->send(new GenericMail($user, $accountDeletionMessages, 'You haven\'t logged in for a while', true));
           }
        });
    }
}
