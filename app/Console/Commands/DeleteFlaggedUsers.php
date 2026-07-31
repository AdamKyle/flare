<?php

namespace App\Console\Commands;

use App\Flare\Jobs\AccountDeletionJob;
use App\Flare\Models\InactiveUserDeletionStatistic;
use App\Flare\Models\User;
use Illuminate\Console\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\ConsoleOutput;

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
        $users = User::where('will_be_deleted', true);
        $userCount = $users->count();

        if ($userCount === 0) {
            return;
        }

        InactiveUserDeletionStatistic::create([
            'deleted_count' => $userCount,
            'tracked_at' => now(),
        ]);

        $progressBar = new ProgressBar(new ConsoleOutput, $userCount);

        $users->chunkById(100, function ($users) use ($progressBar) {
            foreach ($users as $user) {
                $progressBar->advance();

                AccountDeletionJob::dispatch($user)->onConnection('long_running');
            }
        });

        $progressBar->finish();
    }
}
