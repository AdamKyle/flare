<?php

namespace Database\Factories;

use App\Flare\Models\Character;
use App\Flare\Models\CharacterAutomation;
use App\Flare\Models\Faction;
use App\Flare\Models\FactionLoyalty;
use App\Flare\Models\FactionLoyaltyAutomation;
use App\Flare\Models\FactionLoyaltyAutomationLog;
use App\Flare\Models\FactionLoyaltyAutomationWarning;
use App\Flare\Models\FactionLoyaltyNpc;
use App\Flare\Models\GameClass;
use App\Flare\Models\GameMap;
use App\Flare\Models\GameRace;
use App\Flare\Models\Npc;
use App\Flare\Models\User;
use App\Flare\Values\AttackTypeValue;
use App\Flare\Values\AutomationType;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class FactionLoyaltyAutomationWarningFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FactionLoyaltyAutomationWarning::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $character = Character::factory()->create([
            'user_id' => User::factory()->create()->id,
            'name' => Str::random(10),
            'game_class_id' => GameClass::factory()->create()->id,
            'game_race_id' => GameRace::factory()->create()->id,
        ]);
        $gameMap = GameMap::factory()->create();
        $faction = Faction::create([
            'character_id' => $character->id,
            'game_map_id' => $gameMap->id,
            'current_level' => 0,
            'current_points' => 0,
            'points_needed' => 0,
            'maxed' => false,
        ]);
        $factionLoyalty = FactionLoyalty::factory()->create([
            'faction_id' => $faction->id,
            'character_id' => $character->id,
            'is_pledged' => true,
        ]);
        $npc = Npc::factory()->create([
            'game_map_id' => $gameMap->id,
        ]);
        $factionLoyaltyNpc = FactionLoyaltyNpc::factory()->create([
            'faction_loyalty_id' => $factionLoyalty->id,
            'npc_id' => $npc->id,
        ]);
        $characterAutomation = CharacterAutomation::factory()->create([
            'character_id' => $character->id,
            'type' => AutomationType::FACTION_LOYALTY,
            'attack_type' => AttackTypeValue::ATTACK,
        ]);
        $factionLoyaltyAutomation = FactionLoyaltyAutomation::factory()->create([
            'character_automation_id' => $characterAutomation->id,
            'character_id' => $character->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
        ]);
        $factionLoyaltyAutomationLog = FactionLoyaltyAutomationLog::factory()->create([
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'fight_logs' => [
                [
                    'log_entry_id' => (string) Str::uuid(),
                    'outcome' => 'warning',
                ],
            ],
            'crafting_logs' => [],
        ]);
        $fightLogs = $factionLoyaltyAutomationLog->fight_logs;

        return [
            'character_id' => $character->id,
            'faction_loyalty_automation_id' => $factionLoyaltyAutomation->id,
            'faction_loyalty_automation_log_id' => $factionLoyaltyAutomationLog->id,
            'faction_loyalty_npc_id' => $factionLoyaltyNpc->id,
            'log_type' => 'fight_logs',
            'log_entry_id' => $fightLogs[0]['log_entry_id'],
            'type' => 'warning',
            'message' => 'Warning message.',
        ];
    }
}
