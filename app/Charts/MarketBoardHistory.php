<?php

declare(strict_types = 1);

namespace App\Charts;

use App\Flare\Models\MarketBoard;
use App\Flare\Models\MarketHistory;
use Chartisan\PHP\Chartisan;
use ConsoleTVs\Charts\BaseChart;
use Illuminate\Http\Request;

class MarketBoardHistory extends BaseChart
{

    public ?string $name       = 'market_board_history';

    public ?string $routeName  = 'market_board_history';

    public ?array $middlewares = ['auth'];

    /**
     * Handles the HTTP request for the given chart.
     * It must always return an instance of Chartisan
     * and never a string or an array.
     */
    public function handler(Request $request): Chartisan
    {

        $listings = MarketBoard::with('item')->get();

        return Chartisan::build()
            ->labels($listings->pluck('item.affix_name')->toArray())
            ->dataset('Current Listings', $listings->pluck('listed_price')->toArray());
    }
}
