<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use App\Game\Character\CharacterAttack\Events\UpdateCharacterAttackEvent;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Traits\UpdateMarketBoard;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

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
        if (! Inventory::where('character_id', $this->character->id)->exists()) {
            $this->character->user()->update(['will_be_deleted' => true]);
            Log::warning('Character attack cache deduction job stopped for a character with missing inventory.', [
                'character_id' => $this->character->id,
                'user_id' => $this->character->user_id,
                'job' => self::class,
            ]);

            return;
        }

        try {
            $buildCharacterAttackTypes->buildCache($this->character);
        } catch (MissingInventoryException $exception) {
            $this->character->user()->update(['will_be_deleted' => true]);
            Log::warning('Character attack cache deduction job stopped for a character with missing inventory.', [
                'character_id' => $this->character->id,
                'user_id' => $this->character->user_id,
                'job' => self::class,
                'exception' => $exception,
            ]);

            return;
        }

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
