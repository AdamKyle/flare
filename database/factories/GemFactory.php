<?php

namespace Database\Factories;

use App\Flare\Models\Gem;
use App\Flare\Models\GameLocationGemParamter;
use App\Flare\Models\GameMapGemParamter;
use App\Game\Gems\Values\GemTypeValue;
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
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => 'Sample',
            'domain' => Gem::DOMAIN_CHARACTER,
            'tier' => 1,
            'primary_atonement_type' => GemTypeValue::ICE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::FIRE,
            'primary_atonement_amount' => 0.01,
            'secondary_atonement_amount' => 0.20,
            'tertiary_atonement_amount' => 0.10,
        ];
    }

    public function mapGenerated(?GameMapGemParamter $profile = null): static
    {
        return $this->state(fn (): array => [
            'name' => $profile?->name ?? 'Generated Map Gem',
            'domain' => Gem::DOMAIN_MAP,
            'tier' => null,
            'primary_atonement_type' => null,
            'secondary_atonement_type' => null,
            'tertiary_atonement_type' => null,
            'primary_atonement_amount' => null,
            'secondary_atonement_amount' => null,
            'tertiary_atonement_amount' => null,
            'game_map_gem_paramters_id' => $profile?->id,
        ]);
    }

    public function locationGenerated(?GameLocationGemParamter $profile = null): static
    {
        return $this->state(fn (): array => [
            'name' => $profile?->name ?? 'Generated Location Gem',
            'domain' => Gem::DOMAIN_LOCATION,
            'tier' => null,
            'primary_atonement_type' => null,
            'secondary_atonement_type' => null,
            'tertiary_atonement_type' => null,
            'primary_atonement_amount' => null,
            'secondary_atonement_amount' => null,
            'tertiary_atonement_amount' => null,
            'game_location_gem_paramters_id' => $profile?->id,
        ]);
    }
}
