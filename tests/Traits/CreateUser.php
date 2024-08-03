<?php

namespace Tests\Traits;

use App\Flare\Models\User;
use Spatie\Permission\Models\Role;

trait CreateUser
{
    public function createUser(array $options = []): User
    {
        return User::factory()->create($options);
    }

    public function createAdmin(Role $role, array $options = []): User
    {
        $user = $this->createUser(array_merge($options));

        $user->assignRole($role->name);

        return $user;
    }
}
