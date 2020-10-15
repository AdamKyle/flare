<?php

namespace App\Http\Controllers;

use App\Flare\Models\GameClass;
use App\Flare\Models\GameRace;
use Illuminate\Http\Request;
use Storage;

class InfoPageController extends Controller
{

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function viewPage(string $pageName)
    {
        $files = Storage::disk('info')->files($pageName);

        if (empty($files)) {
            abort(404);
        }

        if (is_null(config('info.' . $pageName))) {
            abort(404);
        }

        $sections = [];

        for ($i = 0; $i < count($files); $i++) {

            $view    = null;
            $livewire = false;

            if (isset(config('info.' . $pageName)[$i])) {
                $view     = config('info.' . $pageName)[$i]['view'];
                $livewire = config('info.' . $pageName)[$i]['livewire'];
            }

            $sections[] = [
                'content'  => Storage::disk('info')->get($files[$i]),
                'view'     => $view,
                'livewire' => $livewire,
            ];
        }

        return view('information.core', [
            'pageTitle' => $pageName,
            'sections'  => $sections,
        ]);
    }

    public function viewRace(GameRace $race) {
        return view('information.races.race', [
            'race' => $race,
        ]);
    }

    public function viewClass(GameClass $class) {
        return view('information.classes.class', [
            'class' => $class,
        ]);
    }
}
