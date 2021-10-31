<?php

namespace App\Game\Adventures\Services;

use App\Flare\Builders\CharacterInformationBuilder;
use App\Flare\Models\Adventure;
use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
use App\Flare\Models\Monster;
use App\Flare\Services\FightService;
use App\Game\Adventures\Builders\RewardBuilder;
use App\Game\Adventures\Traits\CreateBattleMessages;
use Illuminate\Support\Str;

class AdventureFightService {

    use CreateBattleMessages;

    /**
     * @var CharacterInformationBuilder $characterInformation
     */
    private $characterInformation;
    
    private $rewardBuilder;

    private $fightService;

    /**
     * @var Character $character
     */
    private $character;

    /**
     * @var Adventure $adventure
     */
    private $adventure;

    private $battleLogs = [];

    private $battleMessages = [];

    private $originalRewardData = [];
    
    private $rewardData = [];

    private $rewardDataLogs = [];

    private $characterWon = true;

    private $attackType = null;

    private $monsterName = null;

    private $currentCharacterHealth;

    /**
     * Constructor
     *
     * @param Character $character
     * @param Adventure $adventure
     * @return void
     */
    public function __construct(
        CharacterInformationBuilder $characterInformationBuilder, 
        FightService $fightService,
        RewardBuilder $rewardBuilder,
    ) {

        $this->characterInformation = $characterInformationBuilder;
        $this->fightService         = $fightService;
        $this->rewardBuilder        = $rewardBuilder;
    }

    public function setCharacter(Character $character, string $attackType): AdventureFightService {
        $this->character     = $character;
        $this->attackType    = $attackType;

        $voided = $this->isAttackVoided($attackType);

        $this->characterInformation   = $this->characterInformation->setCharacter($character);

        $this->currentCharacterHealth = $this->characterInformation->buildHealth($voided);

        return $this;
    }

    public function setAdventure(Adventure $adventure): AdventureFightService {
        $this->adventure = $adventure;

        return $this;
    }
    
    public function setRewards(array $rewards): AdventureFightService {
        $this->rewardData         = $rewards;
        $this->originalRewardData = $rewards;
        
        return $this;
    }

    /**
     * Process the battle
     *
     * @return void
     */
    public function processFloor() {
        $monsterCount = $this->adventure->monsters->count();

        if ($monsterCount > 1) {
            $this->handleMultipleEncounters($monsterCount);

            return;
        }

        $this->fight();
    }

    public function getLogs(): array {
        return  [
            'reward_info' => $this->rewardDataLogs,
            'messages'    => $this->battleMessages,
            'won_fight'   => $this->characterWon,
        ];
    }

    protected function handleMultipleEncounters(int $monsterCount) {
        $encounters = rand(1, $monsterCount);

        for ($i = 1; $i <= $encounters; $i++) {
            if ($this->characterWon) {
                $this->fight();
            }
        }
    }

    protected function fight() {
        $monster            = $this->adventure->monsters()->inRandomOrder()->first();
        $this->monsterName  = $monster->name . '-' . Str::random(10);
        $message            = 'You encounter a: ' . $monster->name;
        $this->battleLogs   = $this->addMessage($message, 'info-encounter');
        $this->characterWon = $this->fightService->processFight($this->character, $monster, $this->attackType);

        if ($this->characterWon) {
            $this->processRewards($monster);
        }

        $logs = [...$this->battleLogs, ...$this->fightService->getBattleMessages()];

        $this->battleLogs = [];

        $this->battleMessages[$this->monsterName] = $logs;

        $this->fightService->reset();
    }

    protected function isAttackVoided(string $attackType): bool {
        return str_contains($attackType, 'voided');
    }
    
    protected function processRewards(Monster $monster) {
        $rewardData = [];

        $gameMap = $this->character->map->gameMap;

        $this->handleXP($monster, $gameMap);
        $this->handleRewards($monster, $gameMap);

        $rewardData[$this->monsterName] = $this->rewardData;

        $this->rewardDataLogs = array_merge($this->rewardDataLogs, $rewardData);

        $this->rewardData = $this->originalRewardData;
    }
    
    protected function handleXP(Monster $monster, GameMap $gameMap) {
        $xpReduction = 0.0;

        if (isset($this->rewardData['skill'])) {
            $xpReduction = $this->rewardData['skill']['exp_towards'];

            $foundSkill = $this->character->skills()->join('game_skills', function($join) {
                $join->on('game_skills.id', 'skills.game_skill_id')
                    ->where('game_skills.name', $this->rewardData['skill']['skill_name']);
            })->first();

            $this->rewardData['skill']['exp'] = $this->rewardBuilder->fetchSkillXPReward($foundSkill, $this->adventure);
        }

        $xpBonus = $this->adventure->exp_bonus;

        if (is_null($gameMap->xp_bonus)) {
            $xpBonus += $gameMap->xp_bonus;
        }

        $xpReward = $this->rewardBuilder->fetchXPReward($monster, $this->character->level, $xpReduction);

        $this->rewardData['exp'] = $xpReward + $xpReward * $xpBonus;
    }

    protected function handleRewards(Monster $monster, GameMap $gameMap) {
        $dropChanceBonus   = 0.0;

        if (!is_null($gameMap->drop_chance_bonus)) {
            $dropChanceBonus = $gameMap->drop_chance_bonus;
        }

        $drop      = $this->rewardBuilder->fetchDrops($monster, $this->character, $this->adventure, $dropChanceBonus);
        $questDrop = $this->rewardBuilder->fetchQuestItemFromMonster($monster, $this->character, $this->adventure, $this->rewardData, $dropChanceBonus);
        $gold      = $this->rewardBuilder->fetchGoldRush($monster, $this->character, $this->adventure, $dropChanceBonus);

        if (!is_null($drop)) {
            $this->rewardData['items'][] = [
                'id' => $drop->id,
                'name' => $drop->affix_name,
            ];
        }

        if (!is_null($questDrop)) {
            $this->rewardData['items'][] = [
                'id' => $questDrop->id,
                'name' => $questDrop->affix_name,
            ];
        }

        $this->rewardData['gold'] = $gold;
    }

}
