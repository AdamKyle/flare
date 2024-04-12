<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Flare\Models\Character;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;

class CreateCharacterAttackData implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int $characterId
     */
    public int $characterId;

    /**
     * build character attack data cache
     *
     * @param int $characterId
     */
    public function __construct(int $characterId) {
        $this->characterId = $characterId;
    }

    /**
     * @param BuildCharacterAttackTypes $buildCharacterAttackTypes
     * @return void
     * @throws Exception
     */
    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes): void {
        $buildCharacterAttackTypes->buildCache(Character::find($this->characterId));
    }
}
