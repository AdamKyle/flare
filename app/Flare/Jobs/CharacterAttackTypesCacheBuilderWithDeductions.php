<?php

namespace App\Flare\Jobs;

use Cache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Flare\Services\BuildCharacterAttackTypes;
use App\Game\Core\Traits\UpdateMarketBoard;
use App\Game\Core\Events\UpdateCharacterAttacks;

class CharacterAttackTypesCacheBuilderWithDeductions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    /**
     * @var Character $character
     */
    public Character $character;

    /**
     * @var float $deduction
     */
    public float $deduction;

    /**
     * Create a new job instance.
     *
     * @param Character $character
     * @param float $deduction
     */
    public function __construct(Character $character, float $deduction = 0.0) {
        $this->character         = $character;
        $this->deduction         = $deduction;
    }

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     * @return void
     * @throws Exception
     */
    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes) {

        $buildCharacterAttackTypes->buildCache($this->character);

        $attackData = Cache::get('character-attack-data-' . $this->character->id);

        if ($this->deduction > 0.0) {
            foreach ($attackData as $key => $array) {
                $attackData[$key]['damage_deduction'] = $this->deduction;
            }
        }

        Cache::put('character-attack-data-' . $this->character->id, $attackData);

        $this->updateCharacterStats($this->character, $attackData);
    }

    /**
     * Update the character attack stats
     *
     * @param Character $character
     * @param array $attackDataCache
     * @return void
     */
    protected function updateCharacterStats(Character $character, array $attackDataCache) {
        event(new UpdateCharacterAttacks($character->user, $attackDataCache));
    }
}
