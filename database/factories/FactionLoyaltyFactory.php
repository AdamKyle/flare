<?php

namespace Database\Factories;

use App\Flare\Models\FactionLoyalty;
use Illuminate\Database\Eloquent\Factories\Factory;

class FactionLoyaltyFactory extends Factory {
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FactionLoyalty::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition() {
        return [
            'faction_id'    => null,
            'character_id'  => null,
            'is_pledged'    => true,
        ];
    }
}
