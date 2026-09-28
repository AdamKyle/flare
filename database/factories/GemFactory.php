<?php

namespace Database\Factories;

use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Flare\Models\Gem;
use Illuminate\Database\Eloquent\Factories\Factory;

class GemFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Gem::class;

    /**
     * Define the model's default character-domain Gem state.
     *
     * @return array
     */
    public function definition(): array
    {
        return [
            'name' => 'Sample',
            'domain' => Gem::DOMAIN_CHARACTER,
            'tier' => 1,
        ];
    }

    /**
     * Configure the Gem as a Map-generated World Gem.
     *
     * @param GameMapGemParamter|null $profile
     * @return static
     */
    public function mapGenerated(?GameMapGemParamter $profile = null): static
    {
        return $this->state(fn (): array => [
            'name' => $profile?->name ?? 'Generated Map Gem',
            'domain' => Gem::DOMAIN_MAP,
            'tier' => null,
            'game_map_gem_paramters_id' => $profile?->id,
        ]);
    }

    /**
     * Configure the Gem as a Location-generated World Gem.
     *
     * @param GameLocationGemParamter|null $profile
     * @return static
     */
    public function locationGenerated(?GameLocationGemParamter $profile = null): static
    {
        return $this->state(fn (): array => [
            'name' => $profile?->name ?? 'Generated Location Gem',
            'domain' => Gem::DOMAIN_LOCATION,
            'tier' => null,
            'game_location_gem_paramters_id' => $profile?->id,
        ]);
    }
}
