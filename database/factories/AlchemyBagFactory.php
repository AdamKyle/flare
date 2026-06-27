<?php

namespace Database\Factories;

use App\Flare\Models\AlchemyBag;
use Illuminate\Database\Eloquent\Factories\Factory;

class AlchemyBagFactory extends Factory
{
    protected $model = AlchemyBag::class;

    public function definition(): array
    {
        return [
            'character_id' => 0,
        ];
    }
}
