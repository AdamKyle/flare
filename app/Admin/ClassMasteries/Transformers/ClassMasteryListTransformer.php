<?php

namespace App\Admin\ClassMasteries\Transformers;

use App\Flare\Models\GameClassSpecial;
use League\Fractal\TransformerAbstract;

class ClassMasteryListTransformer extends TransformerAbstract
{
    /**
     * Transform a Class Mastery into its Admin list-row representation.
     */
    public function transform(GameClassSpecial $gameClassSpecial): array
    {
        return [
            'id' => $gameClassSpecial->id,
            'name' => $gameClassSpecial->name,
            'game_class' => [
                'id' => $gameClassSpecial->gameClass->id,
                'name' => $gameClassSpecial->gameClass->name,
            ],
            'type' => $this->resolveType($gameClassSpecial),
            'requires_class_rank_level' => $gameClassSpecial->requires_class_rank_level,
        ];
    }

    /**
     * Derive the admin-display-only Attack/Passive type from the specialty damage value.
     */
    private function resolveType(GameClassSpecial $gameClassSpecial): string
    {
        return $gameClassSpecial->specialty_damage > 0 ? 'attack' : 'passive';
    }
}
