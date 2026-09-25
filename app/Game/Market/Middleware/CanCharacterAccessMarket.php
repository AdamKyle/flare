<?php

namespace App\Game\Market\Middleware;

use App\Game\Market\Services\MarketAccessService;
use Closure;
use Illuminate\Http\Request;

class CanCharacterAccessMarket
{
    /**
     * @param MarketAccessService $marketAccessService
     */
    public function __construct(private readonly MarketAccessService $marketAccessService) {}

    /**
     * Only allow Admins and characters standing on a port to access the Market.
     *
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next): mixed
    {
        if ($this->marketAccessService->canAccess($request->user())) {
            return $next($request);
        }

        $message = 'You must first travel to a port to access the market board. Ports are blue ship icons on the map.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $message], 422);
        }

        return redirect()->route('game')->with('error', $message);
    }
}
