<?php

namespace App\Admin\Locations\Controllers;

use App\Flare\Models\Location;
use App\Flare\Models\Quest;
use App\Game\Maps\Values\LocationType;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class LocationsController extends Controller
{
    /**
     * Render the Admin Locations application shell.
     *
     * @return View Admin Locations application shell view.
     */
    public function index(): View
    {
        return view('admin.locations.index');
    }

    /**
     * Render the legacy informational Location page for the given Location.
     *
     * @param  Location  $location  Location to describe.
     * @return View Legacy informational Location page view.
     */
    public function show(Location $location): View
    {
        $locationType = null;
        $usedInQuest = null;

        if (! is_null($location->questRewardItem)) {
            $questItemId = $location->quest_reward_item_id;

            $usedInQuest = Quest::where(function ($query) use ($questItemId) {
                $query->where('item_id', $questItemId)
                    ->orWhere('secondary_required_item', $questItemId);
            })
                ->orderByRaw('CASE WHEN item_id = ? THEN 0 ELSE 1 END', [$questItemId])
                ->first();
        }

        if (! is_null($location->type)) {
            $locationType = LocationType::tryFrom($location->type);
        }

        return view('information.locations.location', [
            'location' => $location,
            'locationType' => $locationType,
            'usedInQuest' => $usedInQuest,
        ]);
    }

    /**
     * Redirect legacy Location edit links into the modern Admin Locations application.
     *
     * @return RedirectResponse Redirect to the Admin Locations application.
     */
    public function edit(): RedirectResponse
    {
        return redirect()->route('admin.locations.index');
    }
}
