<?php

namespace App\Charts;

use Chartisan\PHP\Chartisan;
use ConsoleTVs\Charts\BaseChart;
use Illuminate\Http\Request;
use App\Flare\Models\Character;

class AllCharacterGoldChart extends BaseChart
{
    public ?string $name       = 'all_character_gold';

    public ?string $routeName  = 'all_character_gold';

    public ?array $middlewares = ['auth'];

    /**
     * Handles the HTTP request for the given chart.
     * It must always return an instance of Chartisan
     * and never a string or an array.
     */
    public function handler(Request $request): Chartisan
    {

        $charactersWithHighGold = Character::where('gold', '>=', 1000000000)->get();

        return Chartisan::build()
            ->labels($charactersWithHighGold->pluck('name')->toArray())
            ->dataset('details', $charactersWithHighGold->pluck('gold')->toArray());
    }
}
