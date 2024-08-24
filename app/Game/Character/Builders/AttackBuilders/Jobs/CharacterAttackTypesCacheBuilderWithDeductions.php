<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Core\Traits\UpdateMarketBoard;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CharacterAttackTypesCacheBuilderWithDeductions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, UpdateMarketBoard;

    public Character $character;

    public float $deduction;

    /**
     * Create a new job instance.
     */
    public function __construct(Character $character, float $deduction = 0.0)
    {
        $this->character = $character;
        $this->deduction = $deduction;
    }

    /**
     * @return void
     *
     * @throws Exception
     */
    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes)
    {

        $buildCharacterAttackTypes->buildCache($this->character);

        $this->updateCharacterStats($this->character);
    }

    /**
     * Update the character attack stats
     *
     * @return void
     */
    protected function updateCharacterStats(Character $character)
    {
        event(new UpdateCharacterAttackEvent($character, false));
    }
}
