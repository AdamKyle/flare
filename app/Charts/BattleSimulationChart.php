<?php

namespace App\Charts;

use App\Flare\Models\CharacterSnapShot;
use App\Flare\Models\Monster;
use Chartisan\PHP\Chartisan;
use ConsoleTVs\Charts\BaseChart;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * Battle simmulation chart.
 * 
 * This chart is registered in the Admin Service Provider and used
 * from there.
 */
class BattleSimulationChart extends BaseChart
{
    public ?string $name      = 'battle_simmulation_chart';

    public ?string $routeName = 'battle_simmulation_chart';

    /**
     * Handles the HTTP request for the given chart.
     * It must always return an instance of Chartisan
     * and never a string or an array.
     */
    public function handler(Request $request): Chartisan
    {
        $monster   = Monster::find($request->monsterId);
        $snapShots = CharacterSnapShot::where('battle_simmulation_data->monster_id', $monster->id)->get();

        return Chartisan::build()
            ->labels(['Characters Alive', 'Characters Dead', 'Battle Took Too Long'])
            ->dataset('information', [$this->fetchAllLivingCharacters($snapShots), $this->fetchAllDeadCharacters($snapShots), $this->fetchTookTooLong($snapShots)]);
    }

    protected function fetchAllDeadCharacters(Collection $snapShots): int {
        $deadPeopleCount = 0;

        foreach ($snapShots as $snapShot) {
            foreach ($snapShot->battle_simmulation_data as $data) {
                if (is_array($data)) {
                    if ($data['character_dead'] && !$data['monster_dead']) {
                        $deadPeopleCount += 1;

                        break;
                    }
                }
            }
        }

        return $deadPeopleCount;
    }

    protected function fetchAllLivingCharacters(Collection $snapShots): int {
        $alivePeopleCount = 0;

        foreach ($snapShots as $snapShot) {
            foreach ($snapShot->battle_simmulation_data as $data) {
                if (is_array($data)) {
                    if (!$data['character_dead'] && $data['monster_dead']) {
                        $alivePeopleCount += 1;
    
                        break;
                    }
                }
            }
        }

        return $alivePeopleCount;
    }

    protected function fetchTookTooLong(Collection $snapShots): int {
        $tookTooLong = 0;

        foreach ($snapShots as $snapShot) {
            foreach ($snapShot->battle_simmulation_data as $data) {
                if (is_array($data)) {
                    if (!$data['character_dead'] && !$data['monster_dead']) {
                        $tookTooLong += 1;
    
                        break;
                    }
                }
            }
        }

        return $tookTooLong;
    }
}