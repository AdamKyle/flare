<?php

namespace Database\Factories;

use App\Flare\Models\GuideQuest;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class GuideQuestFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GuideQuest::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => Str::random(10),
            'intro_text' => Str::random(100),
            'instructions' => Str::random(100),
            'desktop_instructions' => Str::random(100),
            'mobile_instructions' => Str::random(100),
            'xp_reward' => rand(1, 100),
        ];
    }
}
