<?php

namespace App\Game\Gems\Builders;

use App\Flare\Models\Gem;
use App\Game\Gems\Values\GemTierValue;
use App\Game\Gems\Values\GemTypeValue;

class GemBuilder
{
    /**
     * @var array|string[]
     */
    protected array $names = [
        'Rubyvenite',
        'Senamotome',
        'Pezdcreekite',
        'Glinting Bytocchacuaite',
        'Gilty Kobritoid',
        'Haioeite',
        'Vivc',
        'Gunikahnite',
        'Beige Kratndum',
        'Cerise Domandine',
        'Todundum',
        'Pink Mangbazite',
        'Black Ulelcanthite',
        'Pharozoisite',
        'Green Moonniite,',
        'Fresckeite',
        'Espkerite',
        'Tan Abenkite',
        'Lemon Traet',
        'Badgonite',
    ];

    /**
     * Build or return the found gem.
     */
    public function buildGem(int $tier): Gem
    {
        $data = (new GemTierValue($tier))->maxForTier();

        $rolls = [];

        while (count($rolls) !== 3) {
            $roll = rand($data['min'], $data['max']) / 100;

            if (! in_array($roll, $rolls)) {
                $rolls[] = $roll;
            }
        }

        $dataForGem = $this->buildDataForGem($rolls, $tier);

        $gem = $this->findExistingGem($dataForGem);

        if (! is_null($gem)) {
            return $gem;
        }

        return Gem::create($dataForGem);
    }

    /**
     * Find an existing gem based on data
     */
    protected function findExistingGem(array $data): ?Gem
    {
        return Gem::where('name', $data['name'])
            ->where('primary_atonement_type', $data['primary_atonement_type'])
            ->where('secondary_atonement_type', $data['secondary_atonement_type'])
            ->where('tertiary_atonement_type', $data['tertiary_atonement_type'])
            ->where('primary_atonement_amount', $data['primary_atonement_amount'])
            ->where('secondary_atonement_amount', $data['secondary_atonement_amount'])
            ->where('tertiary_atonement_amount', $data['tertiary_atonement_amount'])
            ->first();
    }

    /**
     * Build the data for either finding or building a gem.
     */
    protected function buildDataForGem(array $rolls, int $tier): array
    {
        return [
            'name' => $this->names[rand(0, count($this->names) - 1)],
            'tier' => $tier,
            'primary_atonement_type' => GemTypeValue::FIRE,
            'secondary_atonement_type' => GemTypeValue::WATER,
            'tertiary_atonement_type' => GemTypeValue::ICE,
            'primary_atonement_amount' => $rolls[0],
            'secondary_atonement_amount' => $rolls[1],
            'tertiary_atonement_amount' => $rolls[2],
        ];
    }
}
