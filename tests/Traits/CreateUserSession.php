<?php

namespace Tests\Traits;

use App\Flare\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

trait CreateUserSession
{
    public function createUserSession(User $user): User
    {
        DB::table('sessions')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'testing',
            'payload' => '',
            'last_activity' => time(),
        ]);

        return $user->fresh();
    }
}
