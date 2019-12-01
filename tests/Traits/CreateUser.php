<?php

namespace Tests\Traits;

use App\User;

trait CreateUser {

    public function createUser(array $options = []): User {
        return factory(User::class)->create($options);
    }
}
