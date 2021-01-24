<?php

namespace Tests\Traits;

use App\Flare\Models\Role;

trait CreateRole {

    public function createAdminRole(): Role {
        return Role::create(['name' => 'Admin']);
    }
}
