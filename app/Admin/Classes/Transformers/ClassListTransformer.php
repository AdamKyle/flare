<?php

namespace App\Admin\Classes\Transformers;

use App\Flare\Models\GameClass;
use League\Fractal\TransformerAbstract;

class ClassListTransformer extends TransformerAbstract
{
    /**
     * Transform a Class into its Admin list-row representation.
     */
    public function transform(GameClass $gameClass): array
    {
        return [
            'id' => $gameClass->id,
            'name' => $gameClass->name,
            'damage_stat' => $gameClass->damage_stat,
            'to_hit_stat' => $gameClass->to_hit_stat,
            'has_unlock_requirements' => ! is_null($gameClass->primary_required_class_id),
            'primary_required_class' => $this->transformRelated($gameClass->primaryClassRequired),
            'secondary_required_class' => $this->transformRelated($gameClass->secondaryClassRequired),
            'primary_required_class_level' => $gameClass->primary_required_class_level,
            'secondary_required_class_level' => $gameClass->secondary_required_class_level,
        ];
    }

    /**
     * Transform a related Class into its compact identity representation.
     */
    private function transformRelated(?GameClass $related): ?array
    {
        if (is_null($related)) {
            return null;
        }

        return [
            'id' => $related->id,
            'name' => $related->name,
        ];
    }
}
