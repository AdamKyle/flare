<?php

namespace App\Game\Character\Builders\AttackBuilders\Jobs;

use App\Flare\Models\Character;
use App\Flare\Models\Inventory;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Character\Builders\AttackBuilders\Handler\UpdateCharacterAttackTypesHandler;
use App\Game\Character\CharacterSheet\Transformers\CharacterSheetBaseInfoTransformer;
use App\Game\Character\Exceptions\MissingInventoryException;
use App\Game\Core\Events\UpdateBaseCharacterInformation;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use League\Fractal\Manager;
use League\Fractal\Resource\Item as ResourceItem;

class CharacterAttackTypesCacheBuilder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Character $character;

    public bool $alertStatsUpdated;

    public function __construct(Character $character, bool $alertStatsUpdated = false)
    {
        $this->character = $character;
        $this->alertStatsUpdated = $alertStatsUpdated;
    }

    /**
     * Rebuild the character's attack/stat cache and broadcast the refreshed authoritative character state.
     *
     *
     * @throws Exception
     */
    public function handle(
        UpdateCharacterAttackTypesHandler $updateCharacterAttackTypes,
        Manager $manager,
        CharacterSheetBaseInfoTransformer $characterSheetBaseInfoTransformer,
    ): void {
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

        $character = $this->character->refresh();

        $characterResource = new ResourceItem($character, $characterSheetBaseInfoTransformer);

        event(new UpdateBaseCharacterInformation($character->user, $manager->createData($characterResource)->toArray()));

        if ($this->alertStatsUpdated) {
            event(new AutomationLogUpdate($character->user->id, 'Character stats have been updated.', false, true));
        }
    }
}
