<?php

namespace App\Game\Automation\Jobs;

use App\Admin\Events\ExplorationMonitoringUpdated;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\Character;
use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationLog;
use App\Flare\Models\ExplorationWarning;
use App\Flare\Models\Monster;
use App\Flare\Models\Monster;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Services\CharacterRewardService;
use App\Flare\Values\AutomationType;
use App\Flare\Values\MaxCurrenciesValue;
use App\Flare\Values\MaxCurrenciesValue;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationLogUpdate;
use App\Game\Automation\Events\AutomationStatus;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Events\AutomationTimeOut;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Services\ExplorationCreatureCountCalculator;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Services\ExplorationLogService;
use App\Game\Automation\Services\ExplorationWarningService;
use App\Game\Automation\Services\ExplorationWarningService;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Events\UpdateCharacterStatus;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Handlers\BattleEventHandler;
use App\Game\Battle\Services\MonsterFightService;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\BattleRewardProcessing\Handlers\FactionHandler;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Character\Builders\AttackBuilders\CharacterCacheData;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Events\UpdateCharacterCurrenciesEvent;
use App\Game\Core\Traits\SafelyBroadcastsEvents;
use App\Game\Skills\Services\SkillService;
use App\Game\Skills\Services\SkillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Psr\SimpleCache\InvalidArgumentException;
use Throwable;

class Exploration implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SafelyBroadcastsEvents, SerializesModels;

    const int MAX_ATTEMPTS = 10;

    public Character $character;

    private CharacterRewardService $characterRewardService;

    private SkillService $skillService;

    private MonsterFightService $monsterFightService;

    private CharacterCacheData $characterCacheData;

    private ExplorationCreatureCountCalculator $explorationCreatureCountCalculator;

    private FactionHandler $factionHandler;

    private ExplorationLogService $explorationLogService;

    private ExplorationWarningService $explorationWarningService;

    private ?Monster $monster = null;

    private ?ExplorationLog $explorationLog = null;

    private bool $runtimeMonsterSnapshotRecorded = false;

    private int $automationId;

    private string $attackType;

    private int $timeDelay;

    private int $attempts = 0;

    private array $lastFightData = [];

    private string $currentState = 'initializing';

    private ?string $cancellationReason = null;

    private array $missingFightDataKeys = [];

    private array $battleData = [
        'total_creatures' => 0,
        'total_xp' => 0,
        'total_skill_xp' => 0,
        'total_faction_points' => 0,
        'weapon_damage' => 0,
        'spell_damage' => 0,
        'healing_done' => 0,
        'damage_blocked' => 0,
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
        ExplorationCreatureCountCalculator $explorationCreatureCountCalculator,
        FactionHandler $factionHandler,
        ExplorationLogService $explorationLogService,
        ExplorationWarningService $explorationWarningService,
    ): void {

        $this->characterRewardService = $characterRewardService;

        $this->skillService = $skillService;

        $this->monsterFightService = $monsterFightService;

        $this->characterCacheData = $characterCacheData;

        $this->explorationCreatureCountCalculator = $explorationCreatureCountCalculator;

        $this->factionHandler = $factionHandler;

        $this->explorationLogService = $explorationLogService;

        $this->explorationWarningService = $explorationWarningService;

        try {
            $this->explorationLog = ExplorationLog::where('character_automation_id', $this->automationId)
                ->whereNull('ended_at')
                ->first();

            $automation = CharacterAutomation::where('character_id', $this->character->id)->where('id', $this->automationId)->first();

            if (is_null($automation)) {
                if (! is_null($this->explorationLog)) {
                    $missingAutomationContext = [
                        'character_id' => $this->character->id,
                        'automation_id' => $this->automationId,
                        'exploration_log_id' => $this->explorationLog->id,
                        'cancellation_reason' => 'missing_automation',
                    ];

                    Log::error('Exploration job found no matching automation for an open exploration log.', $missingAutomationContext);
                    Log::channel('exploration_automation')->error(
                        'Exploration job found no matching automation for an open exploration log.',
                        $missingAutomationContext,
                    );

                    $this->explorationLogService->finalize($this->explorationLog, 'missing_automation');
                    $this->explorationWarningService->createWarning(
                        $this->character,
                        $this->explorationLog,
                        'missing_automation',
                        'Exploration ended because the automation was missing. Please report this as a bug.',
                    );

                    $character = $this->character->refresh();

                    $broadcastContext = ['character_id' => $this->character->id, 'automation_id' => $this->automationId];
                    $this->safelyDispatchBroadcastEvent(new UpdateCharacterStatus($character), $broadcastContext);
                    $this->safelyDispatchBroadcastEvent(new AutomationTimeOut($character->user, 0), $broadcastContext);
                }

                return;
            }

            if ($this->shouldBail($automation)) {
                $this->endAutomation($automation, $characterCacheData);

                Cache::delete('can-character-survive-'.$this->character->id);

                return;
            }

            $automation = $this->updateAutomation($automation);

            $params = [
                'selected_monster_id' => $automation->monster_id,
                'attack_type' => $this->attackType,
            ];

            $roundStartedAt = now();

            if ($this->encounter($automation, $params, $this->timeDelay)) {

                if (! is_null($this->explorationLog)) {
                    $this->explorationLogService->recordFightTotals($this->explorationLog, [
                        'fights' => 1,
                        'kills' => $this->battleData['total_creatures'],
                        'weapon_damage' => $this->battleData['weapon_damage'],
                        'spell_damage' => $this->battleData['spell_damage'],
                        'healing_done' => $this->battleData['healing_done'],
                        'damage_blocked' => $this->battleData['damage_blocked'],
                    ], false);
                }

                $time = now()->diffInMinutes($automation->completed_at);

                $delay = $time >= $this->timeDelay ? $this->timeDelay : ($time > 1 ? $time : 0);

                if ($delay === 0) {
                    $rewardContext = [
                        'total_creatures' => $this->battleData['total_creatures'],
                        'total_xp' => $this->battleData['total_xp'],
                        'total_faction_points' => $this->battleData['total_faction_points'],
                        'total_skill_xp' => $this->battleData['total_skill_xp'],
                    ];

                    if (! is_null($this->explorationLog)) {
                        $rewardContext['exploration_log_id'] = $this->explorationLog->id;
                    }

                    $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id'], $rewardContext);

                    $this->endAutomation($automation, $characterCacheData);

                    return;
                }

                $rewardContext = [
                    'total_creatures' => $this->battleData['total_creatures'],
                    'total_xp' => $this->battleData['total_xp'],
                    'total_faction_points' => $this->battleData['total_faction_points'],
                    'total_skill_xp' => $this->battleData['total_skill_xp'],
                ];

                if (! is_null($this->explorationLog)) {
                    $rewardContext['exploration_log_id'] = $this->explorationLog->id;
                }

                $battleEventHandler->processMonsterDeath($this->character->id, $params['selected_monster_id'], $rewardContext);

                // @codeCoverageIgnoreStart
                $delaySeconds = max(0, ($this->timeDelay * 60) - $roundStartedAt->diffInSeconds(now()));
                Exploration::dispatch($this->character, $this->automationId, $this->attackType, $this->timeDelay)->delay(now()->addSeconds($delaySeconds))->onConnection('long_running')->onQueue('exploration');
                // @codeCoverageIgnoreEnd

                return;
            }

            if (is_null(CharacterAutomation::find($automation->id))) {
                return;
            }

            if ($this->attempts >= self::MAX_ATTEMPTS) {
                $this->cancelAutomation(
                    $automation,
                    'Exploration ended because this fight could not be completed. Please review your setup and try again.',
                    'max_attempts_reached',
                );

                return;
            }

            $reason = $this->cancellationReason ?? 'fight_failed';
            $message = $reason === 'malformed_fight_data'
                ? 'Exploration ended because the fight could not be started correctly. Please try again.'
                : 'Exploration ended because the fight could not be completed. Please try again.';

            $this->cancelAutomation($automation, $message, $reason);
        } catch (Throwable $throwable) {
            $this->handleFailure($throwable, 'unexpected_exception');
        }
    }

    /**
     * Handle an encounter.
     *
     * @throws InvalidArgumentException
     */
    private function encounter(CharacterAutomation $automation, array $params, int $timeDelay): bool
    {

        $this->sendOutEventLogUpdate('You and The Guide search the area looking for any other signs of them. That\'s when The Guide spots them and points', true);

        $enemies = $this->explorationCreatureCountCalculator->calculate($this->character);

        if (! is_null($this->explorationLog)) {
            $this->explorationLogService->recordCurrentRoundCreatures($this->explorationLog, $enemies, false);
        }

        $this->sendOutEventLogUpdate('"Chirst, child there are: '.$enemies.' of them ..."
        The Guide hisses at you from the shadows. You ignore his words and prepare for battle. One right after the other ...', true);

        $totalXpToReward = 0;
        $totalSkillXpToReward = 0;
        $factionPointsPerKill = $this->factionHandler->getFactionPointsPerKill($this->character);
        $characterRewardService = $this->characterRewardService->setCharacter($this->character);
        $characterSkillService = $this->skillService->setSkillInTraining($this->character);

        $encounterWeaponDamage = 0;
        $encounterSpellDamage = 0;
        $encounterHealingDone = 0;
        $encounterDamageBlocked = 0;

        for ($creatureCount = 1; $creatureCount <= $enemies; $creatureCount++) {
            if (! $this->fightAutomationMonster($automation, $params)) {
                return false;
            }

            $this->attempts = 0;
            $totalXpToReward += $characterRewardService->fetchXpForMonster($this->monster);
            $totalSkillXpToReward += $characterSkillService->getXpForSkillIntraining($this->character, $this->monster->xp);

            $encounterWeaponDamage += $this->lastFightData['weapon_damage'] ?? 0;
            $encounterSpellDamage += $this->lastFightData['spell_damage'] ?? 0;
            $encounterHealingDone += $this->lastFightData['healing_done'] ?? 0;
            $encounterDamageBlocked += $this->lastFightData['damage_blocked'] ?? 0;

            $messageTotals = $this->extractBattleMessageTotals($this->lastFightData);

            $encounterWeaponDamage += $messageTotals['weapon_damage'];
            $encounterSpellDamage += $messageTotals['spell_damage'];
            $encounterHealingDone += $messageTotals['healing_done'];
            $encounterDamageBlocked += $messageTotals['damage_blocked'];
        }

        $delta = [
            'total_creatures' => $enemies,
            'total_xp' => $totalXpToReward,
            'total_faction_points' => $factionPointsPerKill * $enemies,
            'total_skill_xp' => $totalSkillXpToReward,
        ];

        foreach ($delta as $key => $value) {
            $this->battleData[$key] += $value;
        }

        $this->battleData['weapon_damage'] += $encounterWeaponDamage;
        $this->battleData['spell_damage'] += $encounterSpellDamage;
        $this->battleData['healing_done'] += $encounterHealingDone;
        $this->battleData['damage_blocked'] += $encounterDamageBlocked;

        $this->sendOutEventLogUpdate('The last of the enemies fall. Covered in blood, exhausted, you look around for any signs of more of their friends. The area is silent. "Another day, another battle.
        We managed to survive." The Guide states as he walks from the shadows. The pair of you set off in search of the next adventure ...
        (Exploration will begin again in '.$timeDelay.' minutes)', true);

        return true;
    }

    private function builtMonsterSnapshot(array $fightData): ?array
    {
        if (! isset($fightData['monster']) || ! is_array($fightData['monster'])) {
            return null;
        }

        $builtMonster = $fightData['monster'];
        $monsterId = $builtMonster['id'] ?? null;

        if (is_null($monsterId)) {
            return null;
        }

        $baseMonster = Monster::find($monsterId);

        $maxMonsterHealth = $fightData['health']['max_monster_health'] ?? null;
        $rolledAttack = (int) ($builtMonster['attack_damage'] ?? 0);

        $stats = [
            'str' => $this->builtMonsterStat($builtMonster, $baseMonster, 'str'),
            'dur' => $this->builtMonsterStat($builtMonster, $baseMonster, 'dur'),
            'dex' => $this->builtMonsterStat($builtMonster, $baseMonster, 'dex'),
            'chr' => $this->builtMonsterStat($builtMonster, $baseMonster, 'chr'),
            'int' => $this->builtMonsterStat($builtMonster, $baseMonster, 'int'),
            'agi' => $this->builtMonsterStat($builtMonster, $baseMonster, 'agi'),
            'focus' => $this->builtMonsterStat($builtMonster, $baseMonster, 'focus'),
            'ac' => $this->builtMonsterStat($builtMonster, $baseMonster, 'ac'),
            'health_range' => $this->builtMonsterStat($builtMonster, $baseMonster, 'health_range', 'health', $maxMonsterHealth),
            'attack_range' => $this->builtMonsterStat($builtMonster, $baseMonster, 'attack_range', 'attack_damage'),
            'max_spell_damage' => $this->builtMonsterStat($builtMonster, $baseMonster, 'max_spell_damage', 'spell_damage'),
            'healing_percentage' => $this->builtMonsterStat($builtMonster, $baseMonster, 'healing_percentage', 'healing'),
            'xp' => $this->builtMonsterStat($builtMonster, $baseMonster, 'xp'),
            'gold' => $this->builtMonsterStat($builtMonster, $baseMonster, 'gold'),
            'max_level' => $this->builtMonsterStat($builtMonster, $baseMonster, 'max_level'),
        ];

        if (! is_null($maxMonsterHealth) && $maxMonsterHealth > 0) {
            $stats['health'] = $maxMonsterHealth;
        }

        if ($rolledAttack > 0) {
            $attackDamage = $rolledAttack;
        } else {
            $attackRange = $builtMonster['attack_range'] ?? $baseMonster?->getAttribute('attack_range');
            if (is_string($attackRange) && str_contains($attackRange, '-')) {
                [$min, $max] = array_map('intval', explode('-', $attackRange, 2));
                $attackDamage = ($min === $max) ? $min : rand(min($min, $max), max($min, $max));
            } else {
                $attackDamage = (int) ($attackRange ?? 0);
            }
            $increasesDamageBy = $builtMonster['increases_damage_by'] ?? null;
            if (! is_null($increasesDamageBy)) {
                $attackDamage = (int) ($attackDamage + $attackDamage * $increasesDamageBy);
            }
        }
        $stats['attack_damage'] = $attackDamage;

        return [
            'id' => $monsterId,
            'name' => $builtMonster['name'] ?? $baseMonster?->name,
            'link' => '/monsters/'.$monsterId,
            'stats' => $stats,
        ];
    }

    private function builtMonsterStat(array $builtMonster, ?Monster $baseMonster, string $baseAttribute, ?string $runtimeAttribute = null, mixed $fightValue = null): mixed
    {
        foreach ([
            $fightValue,
            ! is_null($runtimeAttribute) ? $builtMonster[$runtimeAttribute] ?? null : null,
            $builtMonster[$baseAttribute] ?? null,
            ! is_null($runtimeAttribute) ? $baseMonster?->getAttribute($runtimeAttribute) : null,
            $baseMonster?->getAttribute($baseAttribute),
        ] as $value) {
            if (! is_null($value) && $value !== 0) {
                return $value;
            }
        }

        return $builtMonster[$baseAttribute] ?? $baseMonster?->getAttribute($baseAttribute);
    }

    private function extractBattleMessageTotals(array $fightData): array
    {
        $totals = [
            'weapon_damage' => 0,
            'spell_damage' => 0,
            'healing_done' => 0,
            'damage_blocked' => 0,
        ];

        foreach ($fightData['messages'] ?? [] as $messageData) {
            if (! is_array($messageData)) {
                continue;
            }

            $message = $messageData['message'] ?? null;

            if (! is_string($message)) {
                continue;
            }

            $totals['weapon_damage'] += $this->extractWeaponDamageFromMessage($message);
            $totals['spell_damage'] += $this->extractSpellDamageFromMessage($message);
            $totals['healing_done'] += $this->extractHealingFromMessage($message);
            $totals['damage_blocked'] += $this->extractBlockedFromMessage($message);
        }

        return $totals;
    }

    private function extractWeaponDamageFromMessage(string $message): int
    {
        $patterns = [
            '/Your weapon hits .+ for: ([0-9,]+)/',
            '/You hit for \(weapon - double attack\) ([0-9,]+)/',
            '/You hit for \((?:Gunslingers Assassination!|Book Binders Fear|Hammer|Arcane Alchemist Ravenous Dream)\):? ([0-9,]+)/',
            '/You slash, you thrash, you bash and you crash your way through! \(You dealt: ([0-9,]+)\)/',
            '/You strike the enemy in an ambush doing: ([0-9,]+) damage!/',
            '/Your class special: .+ fires off and you do: ([0-9,]+) damage to the enemy!/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    private function extractSpellDamageFromMessage(string $message): int
    {
        $patterns = [
            '/Your damage spell\(s\) hits .+ for: ([0-9,]+)/',
            '/Your spell\(s\) hits for: ([0-9,]+)/',
            '/You hit for \(Arcane Alchemist Ravenous Dream\): ([0-9,]+)/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    private function extractHealingFromMessage(string $message): int
    {
        $patterns = [
            '/gives you life: ([0-9,]+)/',
            '/You healed for: ([0-9,]+)/',
            '/You heal for: ([0-9,]+)/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    private function extractBlockedFromMessage(string $message): int
    {
        $patterns = [
            '/You reduced the incoming \(Physical\) damage with your armour by: ([0-9,]+)/',
            '/You block: ([0-9,]+) of the enemies special attack damage!/',
        ];

        return $this->extractMessageTotal($message, $patterns);
    }

    private function extractMessageTotal(string $message, array $patterns): int
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $message, $matches) === 1) {
                return (int) str_replace(',', '', $matches[1]);
            }
        }

        return 0;
    }

    /**
     * Fight monster through automation.
     *
     * @throws InvalidArgumentException
     */
    private function fightAutomationMonster(CharacterAutomation $automation, array $params): bool
    {
        $this->currentState = 'setting_up_fight';

        $setupData = $this->setUpFightForMonster($params);

        if (! $this->hasRequiredHealthData($setupData)) {
            $this->logMalformedBattleData($automation, 'setupMonster', $setupData);

            return false;
        }

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($automation, $setupData);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        $this->currentState = 'fighting_monster';
        $data = $this->fightMonster();

        if (empty($data)) {
            return false;
        }

        if (! $this->hasRequiredHealthData($data)) {
            $this->logMalformedBattleData($automation, 'fightMonster', $data);

            return false;
        }

        $endedAutomationDueToCharacterDeath = $this->handleWhenCharacterDies($automation, $data);

        if ($endedAutomationDueToCharacterDeath) {
            return false;
        }

        if (! $this->runtimeMonsterSnapshotRecorded && ! is_null($this->explorationLog)) {
            $snapshotData = $setupData;

            if (isset($data['attack_damage']) && $data['attack_damage'] > 0 && isset($snapshotData['monster'])) {
                $snapshotData['monster']['attack_damage'] = $data['attack_damage'];
            }

            $builtMonsterSnapshot = $this->builtMonsterSnapshot($snapshotData);

            if (! is_null($builtMonsterSnapshot)) {
                $this->explorationLogService->recordMonsterSnapshot($this->explorationLog, $builtMonsterSnapshot, false);
            }

            $this->runtimeMonsterSnapshotRecorded = true;
        }

        if (is_null($this->monster)) {
            $this->monster = $this->monsterFightService->getMonster();
        }

        return true;
    }

    /**
     * Handle when a character dies in automation.
     */
    private function handleWhenCharacterDies(CharacterAutomation $automation, array $data): bool
    {
        if ($data['health']['current_character_health'] <= 0) {
            $this->cancelAutomation($automation, 'You died during exploration. Exploration has ended.', 'character_died');

            return true;
        }

        return false;
    }

    private function shouldAttackAgain(array $data): bool
    {

        if (! $this->hasRequiredHealthData($data)) {
            return false;
        }

        if ($data['health']['current_monster_health'] > 0) {
            return true;
        }

        return false;
    }

    private function hasRequiredHealthData(array $data): bool
    {
        return empty($this->missingRequiredHealthData($data));
    }

    private function missingRequiredHealthData(array $data): array
    {
        if (! isset($data['health']) || ! is_array($data['health'])) {
            return ['health'];
        }

        return array_values(array_filter([
            'health.current_character_health',
            'health.current_monster_health',
        ], fn (string $key): bool => ! array_key_exists(str_replace('health.', '', $key), $data['health'])));
    }

    private function logMalformedBattleData(CharacterAutomation $automation, string $source, array $data): void
    {
        $this->cancellationReason = 'malformed_fight_data';
        $this->missingFightDataKeys = $this->missingRequiredHealthData($data);
        $context = [
            'character_id' => $this->character->id,
            'automation_id' => $automation->id,
            'exploration_log_id' => $this->explorationLog?->id,
            'monster_id' => $automation->monster_id,
            'attack_type' => $this->attackType,
            'attempts' => $this->attempts,
            'current_state' => $this->currentState,
            'cancellation_reason' => $this->cancellationReason,
            'source' => $source,
            'missing_or_invalid_payload' => $this->missingFightDataKeys,
        ];

        Log::error('Exploration automation received malformed battle data.', $context);
        Log::channel('exploration_automation')->error('Exploration automation received malformed battle data.', $context);
    }

    /**
     * Should we bail?
     */
    private function shouldBail(CharacterAutomation $automation): bool
    {
        if (now()->greaterThanOrEqualTo($automation->completed_at)) {
            return true;
        }

        return false;
    }

    /**
     * Update automation to select the next monster if that option is available.
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
    private function endAutomation(CharacterAutomation $automation, CharacterCacheData $characterCacheData): void
    {
        $automation->delete();

        $characterCacheData->deleteCharacterSheet($this->character);

        Cache::delete('can-character-survive-'.$this->character->id);

        $this->sendOutEventLogUpdate('"Phew, child! I did not think we would survive all of your shenanigans.
            So many times I could have died! Do you ever think about anyone other than yourself? No? Didn\'t think so." The Guide storms off and you follow him in silence.', true);

        $this->sendOutEventLogUpdate('Your adventures over, you head to back to the nearest town. Upon arriving, you and The Guide spot the closest Inn. Soaked in the
            blood of your enemies, the sweat of the lingers on you like a bad smell. Entering the establishment and finding a table, you are greeted by a big busty women with shaggy long red hair messily tied in a pony tail.
            She leans down to the table, her cleavage close enough to your face that you can see the freckles and lines of age. Her grin missing a tooth, she states: "What can I get the both of ya?" You shutter on the inside.', true);

        $character = $this->character->refresh();

        if (! is_null($this->explorationLog)) {
            $this->explorationLogService->finalize($this->explorationLog, 'natural_end');
            $this->explorationWarningService->createWarning($character, $this->explorationLog, 'natural_end', 'Exploration completed.');
        }

        $this->rewardPlayer($character);

        $broadcastContext = ['character_id' => $this->character->id, 'automation_id' => $this->automationId];
        $this->safelyDispatchBroadcastEvent(new UpdateCharacterStatus($character), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationTimeOut($character->user, 0), $broadcastContext);
        event(new ExplorationMonitoringUpdated($this->character->id));

    }

    /**
     * Cancel automation without rewarding the player.
     */
    private function cancelAutomation(CharacterAutomation $automation, ?string $message = null, string $reason = 'failed'): void
    {
        $automation->delete();

        $this->characterCacheData->deleteCharacterSheet($this->character);

        Cache::delete('can-character-survive-'.$this->character->id);

        $this->logCancellation($automation, $reason);

        $warningMessage = $message ?? 'Exploration ended because an unexpected error occurred. Please report this as a bug.';

        $this->sendOutEventLogUpdate($warningMessage);

        $character = $this->character->refresh();

        if (! is_null($this->explorationLog)) {
            $this->explorationLogService->finalize($this->explorationLog, $reason);
            $this->explorationWarningService->createWarning(
                $character,
                $this->explorationLog,
                $reason,
                $warningMessage,
            );
        }

        $broadcastContext = ['character_id' => $this->character->id, 'automation_id' => $automation->id];
        $this->safelyDispatchBroadcastEvent(new UpdateCharacterStatus($character), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationTimeOut($character->user, 0), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationStatus($character->user, false), $broadcastContext);
        event(new ExplorationMonitoringUpdated($this->character->id));
    }

    /**
     * Set up the fight its self.
     *
     * @throws InvalidArgumentException
     */
    private function setUpFightForMonster(array $params): array
    {
        return $this->monsterFightService->setupMonster($this->character, $params, true, false, true);
    }

    /**
     * Fight the monster.
     *
     * @throws InvalidArgumentException
     */
    private function fightMonster(): array
    {
        $data = $this->monsterFightService->fightMonster($this->character, $this->attackType, false, true);

        $this->lastFightData = $data;

        if ($this->shouldAttackAgain($data) && $this->attempts >= self::MAX_ATTEMPTS) {
            $this->cancellationReason = 'max_attempts_reached';
            $this->sendOutEventLogUpdate('The Guide is growing restless with how long it takes you to kill one monster. "I am bored child!" He grows agitated and decides to walk off. Guess your not strong enough? Either way, you make a run for it to live!', true);

            return [];
        }

        if ($this->shouldAttackAgain($data) && $this->attempts < self::MAX_ATTEMPTS) {
            $this->attempts++;

            return $this->fightMonster();
        }

        return $data;
    }

    public function failed(Throwable $throwable): void
    {
        $this->character = Character::find($this->character->id) ?? $this->character;
        $this->explorationLog = ExplorationLog::where('character_automation_id', $this->automationId)
            ->whereNull('ended_at')
            ->first();
        $this->explorationLogService = new ExplorationLogService;
        $this->explorationWarningService = new ExplorationWarningService;

        (new MonitoredBugReportService)->reportError(
            'exploration-automation',
            $throwable->getMessage(),
            ['character_id' => $this->character->id, 'automation_id' => $this->automationId],
            $throwable::class,
            $this->character->id,
        );

        $this->handleFailure($throwable, 'queue_job_failed');
    }

    private function handleFailure(Throwable $throwable, string $reason): void
    {
        $automation = CharacterAutomation::where('id', $this->automationId)
            ->where('character_id', $this->character->id)
            ->where('type', AutomationType::EXPLORING)
            ->first();
        $context = $this->failureContext($throwable, $reason, $automation);

        Log::error('Exploration automation failed.', $context);
        Log::channel('exploration_automation')->error('Exploration automation failed.', $context);

        if (is_null($automation)) {
            return;
        }

        $automation->delete();
        Cache::delete('character-defence-'.$this->character->id);
        Cache::delete('character-sheet-'.$this->character->id);
        Cache::delete('can-character-survive-'.$this->character->id);

        $warningMessage = 'Exploration stopped because something went wrong. Please try again.';
        $this->sendOutEventLogUpdate($warningMessage);

        if (! is_null($this->explorationLog) && is_null($this->explorationLog->ended_at)) {
            $this->explorationLogService->finalize($this->explorationLog, $reason);

            $warningExists = ExplorationWarning::where(
                'exploration_log_id',
                $this->explorationLog->id,
            )->where('type', $reason)->exists();

            if (! $warningExists) {
                $this->explorationWarningService->createWarning(
                    $this->character,
                    $this->explorationLog,
                    $reason,
                    $warningMessage,
                );
            }
        }

        $character = $this->character->refresh();
        $broadcastContext = ['character_id' => $character->id, 'automation_id' => $this->automationId];
        $this->safelyDispatchBroadcastEvent(new UpdateCharacterStatus($character), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationTimeOut($character->user, 0), $broadcastContext);
        $this->safelyDispatchBroadcastEvent(new AutomationStatus($character->user, false), $broadcastContext);
        event(new ExplorationMonitoringUpdated($this->character->id));
    }

    private function failureContext(
        Throwable $throwable,
        string $reason,
        ?CharacterAutomation $automation,
    ): array {
        return [
            'exception_class' => $throwable::class,
            'exception_message' => $throwable->getMessage(),
            'exception_file' => $throwable->getFile(),
            'exception_line' => $throwable->getLine(),
            'trace' => $throwable->getTraceAsString(),
            'character_id' => $this->character->id,
            'automation_id' => $this->automationId,
            'exploration_automation_id' => $automation?->id,
            'monster_id' => $automation?->monster_id ?? $this->monster?->id,
            'exploration_log_id' => $this->explorationLog?->id,
            'attack_type' => $this->attackType,
            'attempts' => $this->attempts,
            'current_state' => $this->currentState,
            'cancellation_reason' => $reason,
            'missing_fight_data_keys' => $this->missingFightDataKeys,
        ];
    }

    private function logCancellation(CharacterAutomation $automation, string $reason): void
    {
        $context = [
            'character_id' => $this->character->id,
            'automation_id' => $automation->id,
            'exploration_log_id' => $this->explorationLog?->id,
            'monster_id' => $automation->monster_id,
            'attack_type' => $this->attackType,
            'attempts' => $this->attempts,
            'current_state' => $this->currentState,
            'cancellation_reason' => $reason,
            'missing_fight_data_keys' => $this->missingFightDataKeys,
        ];

        Log::warning('Exploration automation cancelled.', $context);
        Log::channel('exploration_automation')->warning('Exploration automation cancelled.', $context);
    }

    /**
     * Send out event log updates
     */
    private function sendOutEventLogUpdate(string $message, bool $makeItalic = false, bool $isReward = false): void
    {
        if ($this->character->isLoggedIn()) {
            $this->safelyDispatchBroadcastEvent(
                new AutomationLogUpdate($this->character->user->id, $message, $makeItalic, $isReward),
                ['character_id' => $this->character->id, 'automation_id' => $this->automationId]
            );
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

        $this->safelyDispatchBroadcastEvent(
            new UpdateCharacterCurrenciesEvent($character->refresh()),
            ['character_id' => $this->character->id, 'automation_id' => $this->automationId]
        );

        $this->sendOutEventLogUpdate('Gained 10k Gold for completing the exploration.', false, true);
    }
}
