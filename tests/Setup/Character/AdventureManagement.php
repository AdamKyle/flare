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
                'Level 1' => [
                    "Goblin-VhaXIEyO7c" => [
                        [
                            "class" => "info-encounter",
                            "message" => "You encounter a: Goblin"
                        ],
                        [
                            "class" => "info-damage",
                            "message" => "TestFighter hit for (weapon): 36"
                        ],
                        [
                            "class" => "action-fired",
                            "message" => "The enemy has been defeated!"
                        ]
                    ]
                ]
            ],
            'rewards' =>
            [
                "Level 1" =>[
                    "Goblin-VhaXIEyO7c" => [
                        "exp" =>3,
                        "gold" =>25,
                        "items" =>[
                            [
                                "id" =>$item->id,
                                "name" =>$item->affix_name
                            ]
                        ],
                        "skill" =>[
                            "exp" =>20,
                            "skill_name" =>"Looting",
                            "exp_towards" =>0.1
                        ]
                    ]
               ],
            ]
        ];
    }
}