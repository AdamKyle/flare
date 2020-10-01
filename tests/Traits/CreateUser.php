<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;
use App\Flare\Models\User;

trait CreateUser {

    public function createUser(array $options = []): User {
        return User::factory()->create($options);
    }

    public function createAdmin(array $options = [], Role $role): User {
        $user =  $this->createUser($options);

        $user->assignRole($role->name);

        return $user;
    }
}
