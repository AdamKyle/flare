<?php

namespace Database\Factories;

use App\Flare\Models\ReleaseNote;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReleaseNoteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReleaseNote::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => null,
            'url' => null,
            'release_date' => null,
            'body' => null,
        ];
    }
}
