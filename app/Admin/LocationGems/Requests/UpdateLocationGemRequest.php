<?php

namespace App\Admin\LocationGems\Requests;

/**
 * Update shares the exact create validation contract; the unique Location Gem
 * profile-per-Location check ignores the route-bound profile itself.
 */
class UpdateLocationGemRequest extends StoreLocationGemRequest {}
