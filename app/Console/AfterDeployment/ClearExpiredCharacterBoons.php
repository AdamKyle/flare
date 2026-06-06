<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterBoon;
use App\Game\Character\Builders\AttackBuilders\Services\BuildCharacterAttackTypes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class ClearExpiredCharacterBoons extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:expired-character-boons';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear expired character boons and rebuild affected character caches';

    public function __construct(
        private readonly BuildCharacterAttackTypes $buildCharacterAttackTypes,
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $completedAt = now();

        $characterIds = CharacterBoon::query()
            ->where('complete', '<=', $completedAt)
            ->distinct()
            ->pluck('character_id');

        CharacterBoon::query()
            ->where('complete', '<=', $completedAt)
            ->delete();

        Character::query()
            ->whereIn('id', $characterIds)
            ->each(function (Character $character): void {
                $this->buildCharacterAttackTypes->buildCache($character);

                Cache::delete('can-character-survive-' . $character->id);
            });
    }
}
