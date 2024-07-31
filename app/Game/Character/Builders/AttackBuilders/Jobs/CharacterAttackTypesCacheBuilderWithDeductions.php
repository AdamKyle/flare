<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Core\Traits\UpdateMarketBoard;
use Cache;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

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

        $this->updateCharacterStats($this->character);
    }

    /**
     * Update the character attack stats
     *
     * @param Character $character
     * @return void
     */
    protected function updateCharacterStats(Character $character) {
        event(new UpdateCharacterAttackEvent($character->user, false));
    }
}
