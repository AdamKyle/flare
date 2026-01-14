<?php

namespace App\Game\Exploration\Jobs;

use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Exploration\Events\ExplorationLogUpdate;
use App\Game\Exploration\Events\ExplorationTimeOut;
use App\Game\Skills\Services\SkillService;
use Psr\SimpleCache\InvalidArgumentException;

class Exploration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const MAX_ATTEMPTS = 10;

    public Character $character;

    private CharacterRewardService $characterRewardService;

    private SkillService $skillService;

    private MonsterFightService $monsterFightService;

    private FactionHandler $factionHandler;

    private ?Monster $monster = null;

    private int $automationId;

    private string $attackType;

    private int $timeDelay;

    private int $attempts = 0;

    private array $battleData = [
        'total_creatures' => 0,
        'total_xp' => 0,
        'total_skill_xp' => 0,
        'total_faction_points' => 0
    ];

    public function __construct(Character $character, int $automationId, string $attackType, int $timeDelay)
    {
        $this->character = $character;
        $this->automationId = $automationId;
        $this->attackType = $attackType;
        $this->timeDelay = $timeDelay;
    }

    public function handle(
        MonsterFightService $monsterFightService,
        BattleEventHandler $battleEventHandler,
        CharacterCacheData $characterCacheData,
        CharacterRewardService $characterRewardService,
        SkillService $skillService,
        FactionHandler $factionHandler,
    ): void {

        $this->characterRewardService = $characterRewardService;

        $this->skillService = $skillService;

        $this->monsterFightService = $monsterFightService;

        $this->factionHandler = $factionHandler;

        $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

        if ($this->shouldBail($automation)) {
            $this->endAutomation($automation, $characterCacheData);

            Cache::delete('can-character-survive-' . $this->character->id);

            return;
        }

        $automation = $this->updateAutomation($automation);

        $params = [
            'selected_monster_id' => $automation->monster_id,
            'attack_type' => $this->attackType,
        ];

        if ($this->encounter($automation, $params, $this->timeDelay)) {

            $time = now()->diffInMinutes($automation->completed_at);

            $delay = $time >= $this->timeDelay ? $this->timeDelay : ($time > 1 ? $time : 0);

            if ($delay === 0) {
                $this->endAutomation($automation, $characterCacheData);

                return;
            }

            $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id'], [
                'total_creatures' => $this->battleData['total_creatures'],
                'total_xp' => $this->battleData['total_xp'],
                'total_faction_points' => $this->battleData['total_faction_points'],
                'total_skill_xp' => $this->battleData['total_skill_xp'],
            ]);

            Exploration::dispatch($this->character, $this->automationId, $this->attackType, $this->timeDelay)->delay(now()->addMinutes($this->timeDelay))->onQueue('default_long');

            return;
        }

        if ($this->attempts >= self::MAX_ATTEMPTS) {
            $automation->delete();

            $character = $this->character->refresh();

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));

            return;
        }

        $automation->delete();

        $this->sendOutEventLogUpdate('Something went wrong with automation. Could not process fight. Automation Canceled.');

        event(new ExplorationTimeOut($this->character->user, 0));
    }

    /**
     * Handle an encounter.
     *
     * @param CharacterAutomation $automation
     * @param array $params
     * @param int $timeDelay
     * @return bool
     * @throws InvalidArgumentException
     */
    private function encounter(CharacterAutomation $automation, array $params, int $timeDelay): bool
    {

        $canSurviveFights = $this->canSurviveFight($automation, $params);

        if ($canSurviveFights) {

            $this->sendOutEventLogUpdate('You and The Guide search the area looking for any other signs of them. That\'s when The Guide spots them and points', true);

            $enemies = rand(10, 25);

            $this->sendOutEventLogUpdate('"Chirst, child there are: ' . $enemies . ' of them ..."
            The Guide hisses at you from the shadows. You ignore his words and prepare for battle. One right after the other ...', true);

            $totalXpToReward = 0;
            $totalSkillXpToReward = 0;
            $totalFactionPoints = 0;
            $characterRewardService = $this->characterRewardService->setCharacter($this->character);
            $characterSkillService = $this->skillService->setSkillInTraining($this->character);

            for ($i = 1; $i <= $enemies; $i++) {
                $totalXpToReward += $characterRewardService->fetchXpForMonster($this->monster);
                $totalSkillXpToReward += $characterSkillService->getXpForSkillIntraining($this->character, $this->monster->xp);
                $totalFactionPoints += $this->factionHandler->getFactionPointsPerKill($this->character);
            }

            $delta = [
                'total_creatures' => $enemies,
                'total_xp' => $totalXpToReward,
                'total_faction_points' => $totalFactionPoints,
                'total_skill_xp' => $totalSkillXpToReward,
            ];

            foreach ($delta as $key => $value) {
                $this->battleData[$key] += $value;
            }

            $this->sendOutEventLogUpdate('The last of the enemies fall. Covered in blood, exhausted, you look around for any signs of more of their friends. The area is silent. "Another day, another battle.
            We managed to survive." The Guide states as he walks from the shadows. The pair of you set off in search of the next adventure ...
            (Exploration will begin again in ' . $timeDelay . ' minutes)', true);

            return true;
        }

        return false;
    }

    /**
     * Fight and process rewards and return true or false.
     *
     * - Uses a cached version to make this faster.
     *
     * @param CharacterAutomation $automation
     * @param array $params
     * @return bool
     * @throws InvalidArgumentException
     */
    private function canSurviveFight(CharacterAutomation $automation, array $params): bool
    {

        if (Cache::has('can-character-survive-' . $this->character->id)) {

            if (is_null($this->monster)) {
                $this->monster = Monster::find($automation->monster_id);
            }

            return true;
        }

        $this->sendOutEventLogUpdate('"Child, I can see a small group of these creature. If we slaughter them we might learn something." The guide insists. "Theres ten of them. Quick, kill them. We will continue the hunt!"');

        $totalXpToReward = 0;
        $totalSkillXpToReward = 0;
        $totalFactionPoints = 0;

        $characterRewardService = $this->characterRewardService->setCharacter($this->character);
        $characterSkillService = $this->skillService->setSkillInTraining($this->character);

        for ($i = 1; $i <= 10; $i++) {
            if (!$this->fightAutomationMonster($automation, $params)) {
                return false;
            }

            if (!is_null($this->monster)) {
                $totalXpToReward += $characterRewardService->fetchXpForMonster($this->monster);
                $totalSkillXpToReward += $characterSkillService->getXpForSkillIntraining($this->character, $this->monster->xp);
                $totalFactionPoints += $this->factionHandler->getFactionPointsPerKill($this->character);
            }

            $this->attempts = 0;
        }

        $this->battleData = [
            'total_creatures' => 10,
            'total_xp' => $totalXpToReward,
            'total_skill_xp' => $totalSkillXpToReward,
            'total_faction_points' => $totalFactionPoints,
        ];

        Cache::put('can-character-survive-' . $this->character->id, true);

        return true;
    }

    /**
     * Fight monster through automation.
     *
     * @param CharacterAutomation $automation
     * @param array $params
     * @return bool
     * @throws InvalidArgumentException
     */
    private function fightAutomationMonster(CharacterAutomation $automation, array $params): bool
    {

        $data = $this->setUpFightForMonster($params);

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($automation, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        $data = $this->fightMonster();

        if (empty($data)) {
            return false;
        }

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($automation, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        if (is_null($this->monster)) {
            $this->monster = $this->monsterFightService->getMonster();
        }

        return true;
    }

    /**
     * Handle when a character dies in automation.
     *
     * @param CharacterAutomation $automation
     * @param array $data
     * @return bool
     */
    private function handleWhenCharacterDies(CharacterAutomation $automation, array $data): bool
    {
        if ($data['health']['current_character_health'] <= 0) {
            $automation->delete();

            $this->sendOutEventLogUpdate('You died during exploration. Exploration has ended.');

            event(new ExplorationTimeOut($this->character->user, 0));

            return true;
        }

        return false;
    }

    private function shouldAttackAgain(array $data): bool
    {

        if ($data['health']['current_monster_health'] > 0) {
            return true;
        }

        return false;
    }

    /**
     * Should we bail?
     */
    private function shouldBail(?CharacterAutomation $automation = null): bool
    {

        if (is_null($automation)) {
            return true;
        }

        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        return false;
    }

    /**
     * Update automation to select the next monster if that option is available.
     *
     * @param CharacterAutomation $automation
     * @return CharacterAutomation
     */
    private function updateAutomation(CharacterAutomation $automation): CharacterAutomation
    {
        if (! is_null($automation->move_down_monster_list_every)) {
            $characterLevel = $this->character->refresh()->level;

            $automation->update([
                'current_level' => $characterLevel,
            ]);

            $automation = $automation->refresh();

            if (($automation->current_level - $automation->previous_level) >= $automation->move_down_monster_list_every) {
                $monster = Monster::find($automation->monster_id);

                $nextMonster = Monster::where('id', '>', $monster->id)->orderBy('id', 'asc')->first();

                if (! is_null($nextMonster)) {
                    $automation->update([
                        'monster_id' => $nextMonster->id,
                        'previous_level' => $characterLevel,
                    ]);
                }
            }
        }

        return $automation->refresh();
    }

    /**
     * End automation.
     */
    private function endAutomation(?CharacterAutomation $automation, CharacterCacheData $characterCacheData): void
    {
        if (! is_null($automation)) {
            $automation->delete();

            $characterCacheData->deleteCharacterSheet($this->character);

            $this->sendOutEventLogUpdate('"Phew, child! I did not think we would survive all of your shenanigans.
            So many times I could have died! Do you ever think about anyone other than yourself? No? Didn\'t think so." The Guide storms off and you follow him in silence.', true);

            $this->sendOutEventLogUpdate('Your adventures over, you head to back to the nearest town. Upon arriving, you and The Guide spot the closest Inn. Soaked in the
            blood of your enemies, the sweat of the lingers on you like a bad smell. Entering the establishment and finding a table, you are greeted by a big busty women with shaggy long red hair messily tied in a pony tail.
            She leans down to the table, her cleavage close enough to your face that you can see the freckles and lines of age. Her grin missing a tooth, she states: "What can I get the both of ya?" You shutter on the inside.', true);

            $character = $this->character->refresh();

            $this->rewardPlayer($character);

            event(new UpdateCharacterStatus($character));

            event(new ExplorationTimeOut($character->user, 0));
        }
    }

    /**
     * Set up the fight its self.
     *
     * @param array $params
     * @return array
     * @throws InvalidArgumentException
     */
    private function setUpFightForMonster(array $params): array
    {
        return $this->monsterFightService->setupMonster($this->character, $params, true);
    }

    /**
     * Fight the monster.
     *
     * @return array
     * @throws InvalidArgumentException
     */
    private function fightMonster(): array
    {
        $data = $this->monsterFightService->fightMonster($this->character, $this->attackType, false, true);

        if ($this->shouldAttackAgain($data) && $this->attempts >= self::MAX_ATTEMPTS) {
            $this->sendOutEventLogUpdate('The Guide is growing restless with how long it takes you to kill one monster. "I am bored child!" He grows agitated and decides to walk off. Guess your not strong enough? Either way, you make a run for it to live!', true);

            return [];
        }

        if ($this->shouldAttackAgain($data) && $this->attempts < self::MAX_ATTEMPTS) {
            $this->attempts++;

            return $this->fightMonster();
        }

        return $data;
    }

    /**
     * Send out event log updates
     *
     * @param string $message
     * @param bool $makeItalic
     * @param bool $isReward
     * @return void
     */
    private function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void
    {
        if ($this->character->isLoggedIn()) {
            event(new ExplorationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward));
        }
    }

    /**
     * Reward the player for automation completion.
     */
    private function rewardPlayer(Character $character): void
    {

        $gold = $character->gold + 10_000;

        if ($gold >= MaxCurrenciesValue::MAX_GOLD) {
            $gold = MaxCurrenciesValue::MAX_GOLD;
        }

        $character->update(['gold' => $gold]);

        event(new UpdateCharacterCurrenciesEvent($character->refresh()));

        $this->sendOutEventLogUpdate('Gained 10k Gold for completing the exploration.', false, true);
    }
}
