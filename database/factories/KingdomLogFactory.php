<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Flare\Models\KingdomLog;

class KingdomLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = KingdomLog::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'character_id'           => null,
            'attacking_character_id' => null,
            'from_kingdom_id'        => null,
            'to_kingdom_id'          => null,
            'status'                 => null,
            'units_sent'             => null,
            'units_survived'         => null,
            'old_buildings'          => null,
            'new_buildings'          => null,
            'old_units'              => null,
            'new_units'              => null,
            'item_damage'            => null,
            'morale_loss'            => null,
            'published'              => null,
            'opened'                 => null,
            'created_at'             => now(),
        ];
    }
}
