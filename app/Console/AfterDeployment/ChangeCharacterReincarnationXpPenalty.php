<?php

namespace App\Console\AfterDeployment;

use App\Flare\Models\Character;
use App\Flare\Services\CharacterRewardService;
use Illuminate\Console\Command;

class ChangeCharacterReincarnationXpPenalty extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'change:character-reincarnation-xp-penalty';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Change character reincarnation xp penalty';

    /**
     * Execute the console command.
     */
    public function handle(CharacterRewardService $characterRewardService)
    {
        Character::where('times_reincarnated', '>=', 1)->chunkById(1000, function ($characters) use ($characterRewardService) {
            foreach ($characters as $character) {
                $xpForNextLevelBase = $this->getXpForNextLevel($character->level);
                $reincarnationPenalty = 0.02 * $character->times_reincarnated;

                $xpForNextLevel = $xpForNextLevelBase + ($xpForNextLevelBase * $reincarnationPenalty);

                $character->update([
                    'xp_penalty' => $reincarnationPenalty,
                    'xp_next' => $xpForNextLevel,
                ]);

                $character = $character->refresh();

                $characterRewardService->setCharacter($character)->handleLevelUp();
            }
        });
    }

    private function getXpForNextLevel(int $currentLevel): int {
        if ($currentLevel > 999) {
            $xpAtLevel1000 = 1000;

            $baseXPFactor = (2000 - $xpAtLevel1000) / pow(1000, 3);

            $xpRequired = $xpAtLevel1000 + $baseXPFactor * pow(($currentLevel - 1000), 3);
            $xpRequired = min($xpRequired, 1000000);

            return (int) $xpRequired;
        }

        return 100;
    }
}
