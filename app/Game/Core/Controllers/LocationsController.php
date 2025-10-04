<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Location;
use App\Http\Controllers\Controller;

class LocationsController extends Controller
{
    public function show(Location $location)
    {
        return view('information.locations.location', [
            'location' => $location,
        ]);
    }
}
