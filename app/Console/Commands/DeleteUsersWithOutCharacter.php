<?php

namespace App\Console\Commands;

use App\Flare\Models\User;
use App\Game\Messages\Models\Message;
use Illuminate\Console\Command;

class DeleteUsersWithOutCharacter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:users-with-out-character';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes all users who do not have a character attached';

    protected array $userIds = [];

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
    public function handle() {

        User::chunkById(250, function($users) {
            foreach ($users as $user) {
                if (is_null($user->character) && !$user->hasRole('Admin')) {
                    $this->userIds[] = $user->id;
                }
            }
        });

        Message::whereIn('user_id', $this->userIds)->delete();
        User::whereIn('id', $this->userIds)->delete();
    }
}
