<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;
use App\Flare\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UpdateUsersForDevelopment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-users:development';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates users in development to use fake passwords and email';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $progressBar = new ProgressBar(new ConsoleOutput(), User::count());

        User::chunkById(100, function($users) use ($progressBar) {
            foreach ($users as $user) {
                if ($user->hasRole('Admin')) {
                    $progressBar->advance();
                    continue;
                }

                if ($user->character->name === 'Credence') {
                    $progressBar->advance();
                    continue;
                }

                if ($user->character->name === 'TestPVPChar') {
                    $progressBar->advance();
                    continue;
                }

                $user->update([
                    'email'    => Str::random(10) . '@test.com',
                    'password' => Hash::make(Str::random(10))
                ]);

                $progressBar->advance();
            }
        });

        $progressBar->finish();
    }
}
