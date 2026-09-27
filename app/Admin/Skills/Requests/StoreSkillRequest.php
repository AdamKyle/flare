<?php

namespace App\Admin\Skills\Requests;

use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSkillRequest extends FormRequest
{
    /**
     * Allow the route middleware to own Admin authorization.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for creating or updating a Skill.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'max_level' => 'required|integer',
            'type' => ['required', 'integer', Rule::enum(SkillTypeValue::class)],
            'game_class_id' => ['nullable', 'integer', Rule::exists('game_classes', 'id')],
            'base_damage_mod_bonus_per_level' => 'nullable|numeric',
            'base_healing_mod_bonus_per_level' => 'nullable|numeric',
            'base_ac_mod_bonus_per_level' => 'nullable|numeric',
            'fight_time_out_mod_bonus_per_level' => 'nullable|numeric',
            'move_time_out_mod_bonus_per_level' => 'nullable|numeric',
            'unit_time_reduction' => 'nullable|numeric',
            'building_time_reduction' => 'nullable|numeric',
            'unit_movement_time_reduction' => 'nullable|numeric',
            'can_train' => 'required|boolean',
            'skill_bonus_per_level' => 'nullable|numeric',
            'is_locked' => 'required|boolean',
            'class_bonus' => 'nullable|numeric',
        ];
    }

    /**
     * Return the Skill validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Skill name.',
            'description.required' => 'Enter a Skill description.',
            'max_level.required' => 'Enter the Skill max level.',
            'type.required' => 'Select a Skill type.',
            'type.enum' => 'Select a valid Skill type.',
        ];
    }
}
