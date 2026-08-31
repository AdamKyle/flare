<?php

namespace App\Admin\Monsters\Requests;

/**
 * Update shares the exact create validation contract; both act on the
 * complete static Monster field set with no fields becoming optional.
 */
class UpdateMonsterRequest extends StoreMonsterRequest {}
