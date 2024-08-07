<?php

namespace App\Http\Controllers;

use App\Flare\Models\ReleaseNote;

class ReleasesController extends Controller
{
    public function index()
    {
        return view('releases.list', [
            'releases' => ReleaseNote::orderBy('release_date', 'desc')->paginate(10),
        ]);
    }
}
