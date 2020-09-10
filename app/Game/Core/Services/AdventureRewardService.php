<?php

namespace App\Game\Core\Services;

use App\Flare\Events\UpdateCharacterAttackEvent;
use App\Flare\Events\UpdateTopBarEvent;
use App\Flare\Models\Character;
use App\Game\Core\Events\AdventureRewardsEvent;
use App\Game\Core\Services\CharacterService;

class AdventureRewardService {

    private $characterService;

    private $messages = [];

    public function __construct(CharacterService $characterService) {

        $this->characterService = $characterService;
    }

    public function distributeRewards(array $rewards, Character $character): AdventureRewardService {
        $character->gold += $rewards['gold'];
        $character->save();

        $this->handleXp($rewards['exp'], $character);
        $this->handleSkillXP($rewards, $character);
        $this->handleItems($rewards['items'], $character);

        return $this;
    }

    public function getMessages(): array {
        return $this->messages;
    }

    protected function handleXp(int $xp, Character $character): void {
        $character->xp += $xp;
        $character->save();

        if ($character->xp >= $character->xp_next) {
            $this->characterService->levelUpCharacter($character);

            $this->messages[] = 'You gained a level! Now level: ' . $this->character->refresh()->level;

            $character->refresh();
        }
    }

    protected function handleSkillXP(array $rewards, Character $character): void {
        if (isset($rewards['skill'])) {
            $skill = $character->skills->filter(function($skill) use($rewards) {
                return $skill->name === $rewards['skill']['skill']['name'];
            })->first();

            $skill->xp += $rewards['skill']['exp'];
            $skill->save();

            if ($skill->xp >= $skill->xp_max) {
                if ($skill->level <= $skill->max_level) {
                    $level      = $skill->level + 1;
                    $skillBonus = $skill->skill_bonus + $skill->skill_bonus_per_level;
    
                    $skill->update([
                        'level'       => $level,
                        'xp_twoards'  => $skill->can_train ? rand(100, 150) : rand(50, 100),
                        'skill_bonus' => $skillBonus,
                        'xp'          => 0
                    ]);

                    $this->messages[] = 'Your skill: ' . $skill->name . ' gained a level!';
                }
            }
        }
    }

    protected function handleItems(array $items, Character $character): void {
        if (!empty($items)) {
            foreach ($items as $item) {
                $character->inventory->slots()->create([
                    'inventory_id' => $character->inventory->id,
                    'item_id'      => $item['id'],
                ]);

                $this->messages[] = 'You gained the item: ' . $item['name'];
            }
        }
    }
}
