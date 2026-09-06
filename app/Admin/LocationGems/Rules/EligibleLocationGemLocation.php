<?php

namespace App\Admin\LocationGems\Rules;

use App\Flare\Models\Location;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class EligibleLocationGemLocation implements ValidationRule
{
    /**
     * Validate that the given Location id exists and is eligible for Location Gems.
     *
     * @param  string  $attribute  Validated field name.
     * @param  mixed  $value  Submitted Location identifier being validated.
     * @param  Closure  $fail  Laravel's validation-failure callback.
     * @return void Invokes the failure callback when the Location is not eligible; otherwise no direct return value.
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $location = Location::eligibleForLocationGems()
            ->whereKey($value)
            ->first();

        if (is_null($location)) {
            $fail('The selected Location is not eligible for Location Gems.');
        }
    }
}
