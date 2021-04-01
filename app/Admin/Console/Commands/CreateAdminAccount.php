<?php

namespace App\Admin\Console\Commands;

use Hash;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Str;
use App\Flare\Mail\GeneratedAdmin;
use App\Flare\Models\User;

class CreateAdminAccount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:admin {email}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an admin.';

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

        if (is_null($this->argument('email'))) {
            return $this->error('Email is required.');
        }

        if (!is_null(User::where('email', $this->argument('email'))->first())) {
            return $this->error('User with that email, already exists.');
        }

        $token = Str::random(80);

        // Create the user:
        $user = User::create([
            'email'            => $this->argument('email'),
            'password'         => Hash::make(Str::random(10)),
            'game_key'         => hash('sha256', $token),
            'private_game_key' => $token,
        ]);

        // Make them an admin
        $user->assignRole('Admin');

        // Verify the user so they don't have to do that extra step.
        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        // Token for password reset:
        $token = app('Password')::getRepository()->create($user);

        // Mail the user their new credentials.
        Mail::to($user->email)->send(new GeneratedAdmin($user, $token));

        return $this->info('User created successfully. Email has been sent.');
    }
}
