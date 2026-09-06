<?php

namespace App\Admin\LocationGems\Requests;

use App\Admin\LocationGems\Rules\EligibleLocationGemLocation;
use App\Game\Gems\Values\GemTypeValue;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationGemRequest extends FormRequest
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
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'location_id' => [
                'required',
                'integer',
                new EligibleLocationGemLocation,
                Rule::unique('game_location_gem_paramters', 'location_id')->ignore($this->route('gameLocationGemParamter')?->id),
            ],
            'monster_atonement' => ['nullable', 'integer', Rule::in(array_keys(GemTypeValue::getNames()))],
            'crafting_skill_ids' => ['nullable', 'array'],
            'crafting_skill_ids.*' => ['integer', Rule::exists('game_skills', 'id')->where('can_train', false)],
        ];

        foreach ($this->rangeFields() as $rangeField) {
            $rules[$rangeField] = $this->rangeRule();
        }

        return $rules;
    }

    /**
     * Return every range field managed by the Location Gem form.
     *
     * @return array<int, string> Range field names.
     */
    private function rangeFields(): array
    {
        return [
            'character_xp_bonus_range',
            'character_class_rank_xp_bonus_range',
            'kingdom_passive_training_reduction_range',
            'gold_gain_range',
            'gold_dust_gain_range',
            'shards_gain_range',
            'copper_coin_gain_range',
            'character_class_specialty_xp_gain_range',
            'crafting_skill_bonus_range',
            'item_drop_chance_increase_range',
            'unique_item_drop_chance_increase_range',
            'mythic_item_drop_chance_increase_range',
            'cosmic_item_drop_chance_increase_range',
            'enemy_strength_increase_range',
            'enemy_healing_increase_range',
            'enemy_spell_evasion_range',
            'enemy_affix_resistance_range',
            'enemy_entrancing_chance_range',
            'enemy_devouring_light_chance_range',
            'enemy_devouring_darkness_chance_range',
            'enemy_ambush_chance_range',
            'enemy_ambush_resistance_range',
            'enemy_counter_chance_range',
            'enemy_counter_resistance_range',
            'enemy_quest_item_drop_chance_increase_range',
            'monster_xp_increase_range',
            'monster_gold_drop_increase_range',
            'monster_atonement_range',
        ];
    }

    /**
     * Build the shared range-field validation rule: nullable, a string of at most 255
     * characters, and, when populated, exactly two nonnegative numeric values separated
     * by one hyphen with no leading negative sign or extra values.
     *
     * @return array<int, mixed> Range field validation rule.
     */
    private function rangeRule(): array
    {
        return [
            'nullable',
            'string',
            'max:255',
            function (string $attribute, mixed $value, Closure $fail): void {
                if (is_null($value) || trim($value) === '') {
                    return;
                }

                if (preg_match('/^\d+(?:\.\d+)?-\d+(?:\.\d+)?$/', $value) !== 1) {
                    $fail('The range must contain exactly two nonnegative numeric values separated by a hyphen.');
                }
            },
        ];
    }
}
