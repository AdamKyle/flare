<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\Exceptions\MissingInventoryException;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CharacterAttackTypesCacheBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Character $character;

    public bool $alertStatsUpdated;

    /**
     * Create a new job instance.
     */
    public function __construct(Character $character, bool $alertStatsUpdated = false)
    {
        $this->character = $character;
        $this->alertStatsUpdated = $alertStatsUpdated;
    }

    /**
     * @throws Exception
     */
    public function handle(UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes): void
    {
        if (! Inventory::where('character_id', $this->character->id)->exists()) {
            $this->character->user()->update(['will_be_deleted' => true]);
            Log::warning('Character attack cache job stopped for a character with missing inventory.', [
                'character_id' => $this->character->id,
                'user_id' => $this->character->user_id,
                'job' => self::class,
            ]);

            return;
        }

        try {
            $updateCharacterAttackTypes->updateCache($this->character);
        } catch (MissingInventoryException $exception) {
            $this->character->user()->update(['will_be_deleted' => true]);
            Log::warning('Character attack cache job stopped for a character with missing inventory.', [
                'character_id' => $this->character->id,
                'user_id' => $this->character->user_id,
                'job' => self::class,
                'exception' => $exception,
            ]);

            return;
        }

        if ($this->alertStatsUpdated) {
            event(new AutomationLogUpdate($this->character->user->id, 'Character stats have been updated.', false, true));
        }
    }
}
