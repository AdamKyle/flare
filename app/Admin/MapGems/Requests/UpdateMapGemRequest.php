<?php

namespace App\Admin\MapGems\Requests;

/**
 * Update shares the exact create validation contract; the unique Map Gem
 * profile-per-Map check ignores the route-bound profile itself.
 */
class UpdateMapGemRequest extends StoreMapGemRequest {}
