<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;
use App\Flare\Models\User;

trait CreateUser {

    public function createUser(array $options = []): User {
        return User::factory()->create($options);
    }

    public function createAdmin(Role $role, array $options = []): User {
        $user =  $this->createUser(array_merge($options, ['is_test' => false]));

        $user->assignRole($role->name);

        return $user;
    }
}
