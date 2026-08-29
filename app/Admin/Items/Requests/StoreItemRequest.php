<?php

namespace App\Admin\Items\Requests;

use App\Game\Core\Items\Values\AlchemyItemType;
use App\Game\Core\Items\Values\ItemCatalogType;
use App\Game\Core\Items\Values\ItemCraftingType;
use App\Game\Core\Items\Values\ItemDefaultPosition;
use App\Game\Core\Items\Values\ItemEffectType;
use App\Game\Core\Items\Values\ItemSpecialtyType;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true; authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            // Step 1: Basic / catalog.
            'name' => 'required|string|max:255',
            'type' => ['required', 'string', Rule::enum(ItemCatalogType::class)],
            'description' => 'required|string',
            'default_position' => ['nullable', 'string', Rule::enum(ItemDefaultPosition::class)],
            'market_sellable' => 'required|boolean',
            'can_drop' => 'required|boolean',
            'cost' => 'nullable|integer|min:0',
            'gold_dust_cost' => 'nullable|integer|min:0',
            'shards_cost' => 'nullable|integer|min:0',
            'copper_coin_cost' => 'nullable|integer|min:0',
            'gold_bars_cost' => 'nullable|integer|min:0',
            'alchemy_type' => ['nullable', 'string', Rule::enum(AlchemyItemType::class)],
            'specialty_type' => ['nullable', 'string', Rule::enum(ItemSpecialtyType::class)],

            // Step 2: Base combat and attributes.
            'base_damage' => 'nullable|integer|min:0',
            'base_ac' => 'nullable|integer|min:0',
            'base_healing' => 'nullable|integer|min:0',
            'base_damage_mod' => 'nullable|numeric',
            'base_ac_mod' => 'nullable|numeric',
            'base_healing_mod' => 'nullable|numeric',
            'str_mod' => 'nullable|numeric',
            'dur_mod' => 'nullable|numeric',
            'dex_mod' => 'nullable|numeric',
            'chr_mod' => 'nullable|numeric',
            'int_mod' => 'nullable|numeric',
            'agi_mod' => 'nullable|numeric',
            'focus_mod' => 'nullable|numeric',
            'ambush_chance' => 'nullable|numeric|min:0',
            'ambush_resistance' => 'nullable|numeric|min:0',
            'counter_chance' => 'nullable|numeric|min:0',
            'counter_resistance' => 'nullable|numeric|min:0',

            // Step 3: Quest / special effects.
            'effect' => ['nullable', 'string', Rule::enum(ItemEffectType::class)],
            'drop_location_id' => 'nullable|integer|exists:locations,id',
            'unlocks_class_id' => 'nullable|integer|exists:game_classes,id',
            'item_skill_id' => 'nullable|integer|exists:item_skills,id',
            'skill_name' => 'nullable|string|max:255',
            'skill_bonus' => 'nullable|numeric',
            'skill_training_bonus' => 'nullable|numeric',
            'fight_time_out_mod_bonus' => 'nullable|numeric',
            'move_time_out_mod_bonus' => 'nullable|numeric',
            'xp_bonus' => 'nullable|numeric',
            'ignores_caps' => 'required|boolean',
            'can_resurrect' => 'required|boolean',
            'resurrection_chance' => 'nullable|numeric|min:0',
            'spell_evasion' => 'nullable|numeric|min:0',
            'artifact_annulment' => 'nullable|numeric|min:0',
            'healing_reduction' => 'nullable|numeric|min:0',
            'affix_damage_reduction' => 'nullable|numeric|min:0',
            'devouring_light' => 'nullable|numeric|min:0',
            'devouring_darkness' => 'nullable|numeric|min:0',

            // Step 4: Crafting.
            'can_craft' => 'required|boolean',
            'craft_only' => 'required|boolean',
            'crafting_type' => ['nullable', 'string', Rule::enum(ItemCraftingType::class)],
            'skill_level_required' => 'nullable|integer|min:0',
            'skill_level_trivial' => 'nullable|integer|min:0',

            // Step 5: Usable / alchemy / boon behavior.
            'usable' => 'required|boolean',
            'can_stack' => 'required|boolean',
            'lasts_for' => 'nullable|integer|min:0',
            'stat_increase' => 'required|boolean',
            'increase_stat_by' => 'nullable|numeric',
            'damages_kingdoms' => 'required|boolean',
            'kingdom_damage' => 'nullable|numeric|min:0',
            'affects_skill_type' => ['nullable', 'integer', Rule::enum(SkillTypeValue::class)],
            'increase_skill_bonus_by' => 'nullable|numeric',
            'increase_skill_training_bonus_by' => 'nullable|numeric',
            'can_use_on_other_items' => 'required|boolean',
            'holy_level' => 'nullable|integer|min:0',
            'gains_additional_level' => 'required|boolean',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter an Item name.',
            'type.required' => 'Select an Item type.',
            'type.enum' => 'The selected Item type is invalid.',
            'description.required' => 'Enter an Item description.',
            'default_position.enum' => 'The selected default position is invalid.',
            'crafting_type.enum' => 'The selected crafting type is invalid.',
            'affects_skill_type.enum' => 'The selected skill type is invalid.',
        ];
    }

    /**
     * Normalize present boolean fields before validation.
     *
     * @return void Merges normalized boolean values into the request input.
     */
    protected function prepareForValidation(): void
    {
        $booleanKeys = [
            'market_sellable', 'can_drop', 'ignores_caps', 'can_resurrect', 'can_craft',
            'craft_only', 'usable', 'can_stack', 'stat_increase', 'damages_kingdoms',
            'can_use_on_other_items', 'gains_additional_level',
        ];

        $normalized = [];

        foreach ($booleanKeys as $key) {
            if ($this->has($key)) {
                $normalized[$key] = $this->boolean($key);
            }
        }

        $this->merge($normalized);
    }
}
