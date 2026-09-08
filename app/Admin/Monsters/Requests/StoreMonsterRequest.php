<?php

namespace App\Admin\Monsters\Requests;

use App\Admin\Monsters\Values\MonsterListCategory;
use App\Game\Core\Values\CoreStatType;
use App\Game\Raids\Values\RaidAttackType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMonsterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'damage_stat' => ['required', 'string', Rule::enum(CoreStatType::class)],
            'game_map_id' => 'required|integer|exists:game_maps,id',
            'max_level' => 'required|integer|min:0',
            'xp' => 'required|integer|min:0',
            'gold' => 'required|integer|min:0',
            'health_range' => 'required|string|max:255',
            'attack_range' => 'required|string|max:255',
            'drop_check' => 'required|numeric|min:0',
            'only_for_location_type' => ['nullable', 'integer', Rule::in(MonsterListCategory::allCategoryLocationTypes())],

            'str' => 'required|integer|min:0',
            'dur' => 'required|integer|min:0',
            'dex' => 'required|integer|min:0',
            'chr' => 'required|integer|min:0',
            'int' => 'required|integer|min:0',
            'agi' => 'required|integer|min:0',
            'focus' => 'required|integer|min:0',
            'ac' => 'required|integer|min:0',

            'accuracy' => 'nullable|numeric|min:0',
            'dodge' => 'nullable|numeric|min:0',
            'criticality' => 'nullable|numeric|min:0',
            'ambush_chance' => 'nullable|numeric|min:0',
            'ambush_resistance' => 'nullable|numeric|min:0',
            'counter_chance' => 'nullable|numeric|min:0',
            'counter_resistance' => 'nullable|numeric|min:0',

            'can_cast' => 'required|boolean',
            'max_spell_damage' => 'nullable|integer|min:0',
            'casting_accuracy' => 'nullable|numeric|min:0',
            'spell_evasion' => 'nullable|numeric|min:0',
            'max_affix_damage' => 'nullable|integer|min:0',
            'affix_resistance' => 'nullable|numeric|min:0',
            'healing_percentage' => 'nullable|numeric|min:0',
            'entrancing_chance' => 'nullable|numeric|min:0',
            'devouring_light_chance' => 'nullable|numeric|min:0',
            'devouring_darkness_chance' => 'nullable|numeric|min:0',
            'life_stealing_resistance' => 'nullable|numeric|min:0',

            'quest_item_id' => 'nullable|integer|exists:items,id',
            'quest_item_drop_chance' => 'nullable|numeric|min:0|max:9.9999',
            'is_celestial_entity' => 'required|boolean',
            'celestial_type' => 'nullable|integer|min:0',
            'gold_cost' => 'nullable|integer|min:0',
            'gold_dust_cost' => 'nullable|integer|min:0',
            'shards' => 'nullable|integer|min:0',

            'is_raid_monster' => 'required|boolean',
            'is_raid_boss' => 'required|boolean',
            'raid_special_attack_type' => ['nullable', 'integer', Rule::enum(RaidAttackType::class)],
            'fire_atonement' => 'nullable|numeric|min:0',
            'ice_atonement' => 'nullable|numeric|min:0',
            'water_atonement' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Monster name.',
            'damage_stat.in' => 'The selected damage stat is invalid.',
            'game_map_id.required' => 'Select which Game Map this Monster belongs to.',
        ];
    }

    /**
     * Normalize present boolean fields before validation.
     */
    protected function prepareForValidation(): void
    {
        $booleanKeys = ['can_cast', 'is_celestial_entity', 'is_raid_monster', 'is_raid_boss'];
        $normalized = [];

        foreach ($booleanKeys as $key) {
            if ($this->has($key)) {
                $normalized[$key] = $this->boolean($key);
            }
        }

        $this->merge($normalized);
    }

    /**
     * Configure the validator instance to enforce raid Monster/boss mutual exclusion.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->boolean('is_raid_monster') && $this->boolean('is_raid_boss')) {
                $validator->errors()->add('is_raid_boss', 'A Monster cannot be both a raid Monster and a raid boss.');
            }
        });
    }
}
