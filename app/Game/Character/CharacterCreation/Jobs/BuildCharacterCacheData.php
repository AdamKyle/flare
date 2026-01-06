<?php

namespace App\Game\Character\CharacterCreation\Jobs;

use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterCreation\Pipeline\Steps\BuildCache;
use App\Game\Character\CharacterCreation\State\CharacterBuildState;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class BuildCharacterCacheData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param int $characterId
     */
    public function __construct(private readonly int $characterId)
    {}

    /**
     * @throws Exception
     */
    public function handle(BuildCache $buildCache): void
    {

        $character = Character::find($this->characterId);

        $buildCache->process($character);
    }
}
