<?php

namespace App\Flare\Services;

use App\Flare\Models\User;

class CanUserEnterSiteService
{
    public function canUserEnterSite(string $email): bool
    {

        if (! config('app.disabled_reg_and_login')) {
            return true;
        }

        $user = User::where('email', $email)->first();

        // Registration is disabled
        if (is_null($user) && config('app.disabled_reg_and_login')) {
            return false;
        }

        // Login is enabled if you are an admin.
        if (is_null($user->character) && config('app.disabled_reg_and_login')) {
            if ($user->hasRole('Admin')) {
                return true;
            }

            return false;
        }

        // Are you a valid character who is allowed to enter?
        if ($user->email === config('app.allowed_email') && config('app.disabled_reg_and_login')) {
            return true;
        }

        return false;
    }
}
