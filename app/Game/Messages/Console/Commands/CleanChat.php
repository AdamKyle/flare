<?php

namespace App\Game\Messages\Console\Commands;

use App\Game\Messages\Models\Message;
use Carbon\Carbon;
use Illuminate\Console\Command;

class CleanChat extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clean:chat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cleans chat messages from the last 90 days.';

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
        Message::where('created_at', '<=', Carbon::today()->subDays(90))->chunkById(100, function ($messages) {
            foreach ($messages as $message) {
                $message->delete();
            }
        });

        $this->info('Messages cleaned');
    }
}
