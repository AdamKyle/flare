<?php

namespace App\Admin\Classes\Requests;

/**
 * Update shares the exact create validation contract; the unlock-requirement
 * cross-field rules also apply, using the route-bound Class to detect a
 * Class requiring itself.
 */
class UpdateClassRequest extends StoreClassRequest {}
