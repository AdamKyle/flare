<?php

namespace App\Console\Commands;

use Mail;
use App\Flare\Mail\GenericMail;
use App\Flare\Models\Character;
use Illuminate\Console\Command;

class TestEmailTemplate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:email-template';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Email Template Configurations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle() {
        $character = Character::where('name', 'Credence')->first();

        Mail::to($character->user->email)->send(new GenericMail(
            $character->user,
            'This is a sample email with out the login content.',
            'Generic Title',
            false)
        );
    }
}
