<?php

namespace Tests\Feature\Admin\GemAbilities;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameGemAbility;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class GemAbilitiesApiControllerTest extends TestCase
{
    use CreateGameGemAbility, CreateRole, CreateUser, RefreshDatabase;

    public function test_admin_can_create_an_active_gem_ability(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/gem-abilities', [
            'name' => 'Flame Burst',
            'description' => 'Deals bonus Fire damage.',
            'ability_type' => 'active',
            'effect_type' => 'bonus_damage',
            'attack_types' => ['attack', 'attack_and_cast'],
            'proc_chance' => 0.20,
            'effect_value' => 0.35,
            'scaling_source' => 'weapon_attack',
            'enabled' => true,
        ]);

        $response->assertCreated()->assertJsonPath('name', 'Flame Burst');
        $this->assertDatabaseHas('game_gem_abilities', ['name' => 'Flame Burst', 'enabled' => true]);
    }

    public function test_passive_ability_rejects_active_only_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->postJson('/api/admin/gem-abilities', [
            'name' => 'Guarding Light',
            'description' => 'Improves defence.',
            'ability_type' => 'passive',
            'effect_type' => 'defence_mod',
            'attack_types' => ['defend'],
            'proc_chance' => 0.20,
            'effect_value' => 0.15,
            'scaling_source' => 'defence',
            'enabled' => true,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['proc_chance', 'scaling_source']);
    }

    public function test_update_name_uniqueness_ignores_the_current_ability(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $ability = $this->createGameGemAbility(['name' => 'Existing Burst']);

        $response = $this->actingAs($admin)->putJson('/api/admin/gem-abilities/'.$ability->id, [
            'name' => 'Existing Burst',
            'description' => 'Updated description.',
            'ability_type' => 'active',
            'effect_type' => 'bonus_damage',
            'attack_types' => ['cast'],
            'proc_chance' => 0.25,
            'effect_value' => 0.40,
            'scaling_source' => 'spell_attack',
            'enabled' => false,
        ]);

        $response->assertOk()->assertJsonPath('enabled', false);
        $this->assertDatabaseHas('game_gem_abilities', ['id' => $ability->id, 'description' => 'Updated description.']);
    }

    public function test_delete_endpoint_does_not_exist(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $ability = $this->createGameGemAbility();

        $this->actingAs($admin)->deleteJson('/api/admin/gem-abilities/'.$ability->id)->assertMethodNotAllowed();
    }
}
