<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\Exceptions\MissingInventoryException;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CreateCharacterAttackData implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $characterId;

    /**
     * build character attack data cache
     */
    public function __construct(int $characterId)
    {
        $this->characterId = $characterId;
    }

    /**
     * @throws Exception
     */
    public function handle(BuildCharacterAttackTypes $buildCharacterAttackTypes): void
    {
        $character = Character::find($this->characterId);

        if (is_null($character)) {
            return;
        }

        if (! Inventory::where('character_id', $character->id)->exists()) {
            $character->user()->update(['will_be_deleted' => true]);
            Log::warning('Character attack data job stopped for a character with missing inventory.', [
                'character_id' => $character->id,
                'user_id' => $character->user_id,
                'job' => self::class,
            ]);

            return;
        }

        try {
            $buildCharacterAttackTypes->buildCache($character);
        } catch (MissingInventoryException $exception) {
            $character->user()->update(['will_be_deleted' => true]);
            Log::warning('Character attack data job stopped for a character with missing inventory.', [
                'character_id' => $character->id,
                'user_id' => $character->user_id,
                'job' => self::class,
                'exception' => $exception,
            ]);
        }
    }
}
