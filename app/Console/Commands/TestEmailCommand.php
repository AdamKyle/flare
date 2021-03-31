<?php

namespace App\Console\Commands;

use App\Flare\Mail\TestEmail;
use Illuminate\Console\Command;
use Mail;

class TestEmailCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Testing Email';

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
       Mail::to('adamkylebalan@gmail.com')->send(new TestEmail('Title Of Email'));
    }
}