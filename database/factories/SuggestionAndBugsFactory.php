<?php

namespace Database\Factories;

use App\Flare\Models\SuggestionAndBugs;
use Illuminate\Database\Eloquent\Factories\Factory;

class SuggestionAndBugsFactory extends Factory
{
    protected $model = SuggestionAndBugs::class;

    public function definition(): array
    {
        return [
            'character_id' => null,
            'title' => $this->faker->sentence(4),
            'type' => 'bug',
            'platform' => 'desktop',
            'description' => $this->faker->paragraph(),
            'uploaded_image_paths' => [],
        ];
    }
}
