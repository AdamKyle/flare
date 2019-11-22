<?php

namespace Tests\Traits;

use App\User;

trait CreateUser {

    public function createUser(array $options = []) {
        return factory(User::class)->create($options);
    }
}
