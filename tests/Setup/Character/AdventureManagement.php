<?php

namespace Tests\Setup\Character;

use App\Flare\Models\Character;
use App\Flare\Models\Adventure;
use App\Flare\Models\Item;
use App\Flare\Models\Skill;

class AdventureManagement {

    private $character;

    private $characterFactory;

    public function __construct(Character $character, CharacterFactory $characterFactory = null) {
        $this->character        = $character;
        $this->characterFactory = $characterFactory;
    }

    public function assignLog(Adventure $adventure, Item $item, string $skillName): AdventureManagement {

        $foundSkill = $this->character->skills->where('name', $skillName)->first();

        if (is_null($foundSkill)) {
            throw new \Exception('not skill named: ' . $skillName . ' Exists on this character.');
        }


        $log = $this->adventureLog($adventure, $item, $foundSkill);

        $this->character->adventureLogs()->create($log);

        return $this;
    }

    public function updateLog(array $changes, int $logId = null): AdventureManagement {
        $log = $this->character->adventureLogs->first();

        if (!is_null($logId)) {
            $log = $this->character->adventureLogs()->find($logId);
        }

        $log->update($changes);

        return $this;
    }

    public function getCharacterFactory(): CharacterFactory {
        return $this->characterFactory;
    }

    public function getCharacter(): Character {
        return $this->character;
    }

    protected function adventureLog(Adventure $adventure, Item $item, Skill $skill) {
        return [
                'character_id'         => $this->character->id,
                'adventure_id'         => $adventure->id,
                'complete'             => true,
                'in_progress'          => false,
                'last_completed_level' => 1,
                'logs'                 => 
                [
                    "vcCBZhAOqy3Dg9V6a1MRWCthCGFNResjhH7ttUsFFpREdVoH9oNqyrjVny3cX8McbjyGHZYeJ8txcTov" => [
                        [
                            [
                            "attacker" => "Kyle Adams",
                            "defender" => "Goblin",
                            "messages" => [
                                "Kyle Adams hit for 30",
                            ],
                            "is_monster" => false,
                            ],
                        ],
                    ]
                ],
                'rewards'              => 
                [
                    "exp" => 100,
                    "gold" => 75,
                    "items" => [
                        [
                        "id" => $item->id,
                        "name" => $item->name,
                        ],
                    ],
                    "skill" => [
                        "exp"         => 1000,
                        "skill_name"  => $skill->name,
                        "exp_towards" => $skill->xp_towards,
                    ],
                ]
            ];
    }
}