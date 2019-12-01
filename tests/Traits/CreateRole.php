<?php

namespace Tests\Traits;

use Spatie\Permission\Models\Role;
use App\Flare\Models\Character;

trait CreateRole {

    public function createAdminRole(): Role {
        return Role::create(['name' => 'Admin']);
    }
}
