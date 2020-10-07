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

            $this->messages[] = 'You gained a level! Now level: ' . $character->refresh()->level;

            $character->refresh();
        }
    }

    protected function handleSkillXP(array $rewards, Character $character): void {
        if (isset($rewards['skill'])) {
            $skill = $character->skills->filter(function($skill) use($rewards) {
                return $skill->name === $rewards['skill']['skill_name'];
            })->first();

            $skill->xp += $rewards['skill']['exp'];
            $skill->save();
            $skill->refresh();
            
            if ($skill->xp >= $skill->xp_max) {
                if ($skill->level <= $skill->max_level) {
                    $level      = $skill->level + 1;
    
                    $skill->update([
                        'level'              => $level,
                        'xp_max'             => $skill->can_train ? rand(100, 150) : rand(100, 200),
                        'base_damage_mod'    => $skill->base_damage_mod + $skill->baseSkill->base_damage_mod_bonus_per_level,
                        'base_healing_mod'   => $skill->base_healing_mod + $skill->baseSkill->base_healing_mod_bonus_per_level,
                        'base_ac_mod'        => $skill->base_ac_mod + $skill->baseSkill->base_ac_mod_bonus_per_level,
                        'fight_time_out_mod' => $skill->fight_time_out_mod + $skill->baseSkill->fight_time_out_mod_bonus_per_level,
                        'move_time_out_mod'  => $skill->mov_time_out_mod + $skill->baseSkill->mov_time_out_mod_bonus_per_level,
                        'skill_bonus'        => $skill->skill_bonus + $skill->baseSkill->skill_bonus_per_level,
                        'xp'                 => 0,
                    ]);

                    $skill->refresh();

                    $this->messages[] = 'Your skill: ' . $skill->name . ' gained a level and is now level: ' . $skill->level;
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
