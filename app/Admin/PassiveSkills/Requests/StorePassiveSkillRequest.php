<?php

namespace App\Admin\PassiveSkills\Requests;

use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StorePassiveSkillRequest extends FormRequest
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
     * Return the validation rules for creating or updating a Passive Skill.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'max_level' => 'required|integer',
            'effect_type' => ['required', 'integer', Rule::in(array_keys(PassiveSkillTypeValue::getNamedValues()))],
            'bonus_per_level' => 'nullable|numeric',
            'resource_bonus_per_level' => 'nullable|integer',
            'capital_city_building_request_travel_time_reduction' => 'nullable|numeric',
            'capital_city_unit_request_travel_time_reduction' => 'nullable|numeric',
            'resource_request_time_reduction' => 'nullable|numeric',
            'parent_skill_id' => ['nullable', 'integer', Rule::exists('passive_skills', 'id')],
            'unlocks_at_level' => 'nullable|integer',
            'hours_per_level' => 'required|integer',
            'is_locked' => 'required|boolean',
            'is_parent' => 'required|boolean',
        ];
    }

    /**
     * Return the Passive Skill validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Passive Skill name.',
            'description.required' => 'Enter a Passive Skill description.',
            'max_level.required' => 'Enter the Passive Skill max level.',
            'effect_type.required' => 'Select a Passive Skill effect.',
            'effect_type.in' => 'Select a valid Passive Skill effect.',
            'hours_per_level.required' => 'Enter the hours required per level.',
        ];
    }

    /**
     * Reject a Passive Skill that names itself as its parent.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $editingPassiveSkillId = $this->route('passiveSkill')?->id;

            if (is_null($editingPassiveSkillId) || $this->input('parent_skill_id') !== $editingPassiveSkillId) {
                return;
            }

            $validator->errors()->add('parent_skill_id', 'A Passive Skill cannot belong to itself.');
        });
    }
}
