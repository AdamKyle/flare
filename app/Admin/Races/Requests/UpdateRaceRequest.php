<?php

namespace App\Admin\Races\Requests;

/**
 * Update shares the exact create validation contract; the image remains
 * optional so an update can leave the current Race image untouched.
 */
class UpdateRaceRequest extends StoreRaceRequest {}
