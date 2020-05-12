<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;
use App\User;

trait CreateUser {

    public function createUser(array $options = []): User {
        return factory(User::class)->create($options);
    }

    public function createAdmin(array $options = [], Role $role): User {
        $user =  $this->createUser($options);

        $user->assignRole($role->name);

        return $user;
    }
}
