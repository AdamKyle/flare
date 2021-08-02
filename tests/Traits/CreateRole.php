<?php

namespace Tests\Traits;

use App\Flare\Models\Role;

trait CreateRole {

    public function createAdminRole(): Role {
        $role = Role::where('name', 'Admin')->first();

        if (is_null($role)) {
            return Role::create(['name' => 'Admin']);
        }

        return $role;
    }
}
