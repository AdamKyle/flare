<?php

namespace App\Game\Core\Controllers;

use App\Flare\Models\Monster;
use App\Game\Core\Monsters\Services\MonsterShowInformationService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;

class MonstersController extends Controller
{
    public function __construct(private readonly MonsterShowInformationService $monsterShowInformationService) {}

    /**
     * Show the Game monster details page for the given Monster.
     *
     * @return Factory|View
     */
    public function show(Monster $monster)
    {
        return view('admin.monsters.monster', $this->monsterShowInformationService->details($monster));
    }
}
