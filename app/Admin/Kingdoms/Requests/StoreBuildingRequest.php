<?php

namespace App\Admin\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreBuildingRequest extends FormRequest
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
     * Return the validation rules for creating or updating a Kingdom Building and its recruitable Units.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'max_level' => 'required|integer',
            'base_durability' => 'required|integer',
            'base_defence' => 'required|integer',
            'required_population' => 'required|integer',
            'is_walls' => 'required|boolean',
            'is_church' => 'required|boolean',
            'is_farm' => 'required|boolean',
            'is_resource_building' => 'required|boolean',
            'trains_units' => 'required|boolean',
            'is_locked' => 'required|boolean',
            'is_special' => 'required|boolean',
            'wood_cost' => 'required|integer',
            'clay_cost' => 'required|integer',
            'stone_cost' => 'required|integer',
            'iron_cost' => 'required|integer',
            'steel_cost' => 'nullable|integer',
            'increase_population_amount' => 'required|integer',
            'increase_morale_amount' => 'required|numeric',
            'decrease_morale_amount' => 'required|numeric',
            'increase_wood_amount' => 'required|numeric',
            'increase_clay_amount' => 'required|numeric',
            'increase_stone_amount' => 'required|numeric',
            'increase_iron_amount' => 'required|numeric',
            'increase_durability_amount' => 'required|numeric',
            'increase_defence_amount' => 'required|numeric',
            'time_to_build' => 'required|numeric',
            'time_increase_amount' => 'required|numeric',
            'units_per_level' => 'nullable|integer',
            'only_at_level' => 'nullable|integer',
            'passive_skill_id' => ['nullable', 'integer', Rule::exists('passive_skills', 'id')],
            'level_required' => 'nullable|integer',
            'unit_ids' => 'present|array',
            'unit_ids.*' => ['integer', 'distinct', Rule::exists('game_units', 'id')],
        ];
    }

    /**
     * Return the Kingdom Building validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Building name.',
            'description.required' => 'Enter a Building description.',
            'max_level.required' => 'Enter the Building max level.',
            'unit_ids.*.exists' => 'Select Units that exist.',
            'unit_ids.*.distinct' => 'Select each Unit only once.',
        ];
    }

    /**
     * Enforce a coherent Unit recruitment schedule for Buildings that train Units.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if (! $this->boolean('trains_units')) {
                return;
            }

            $this->validateRecruitmentSchedule($validator);
        });
    }

    /**
     * Validate the Unit selection against the Building's recruitment schedule.
     *
     * @param Validator $validator
     * @return void
     */
    private function validateRecruitmentSchedule(Validator $validator): void
    {
        $unitCount = count(Arr::wrap($this->input('unit_ids')));
        $unitsPerLevel = $this->integer('units_per_level');

        if ($unitCount === 0) {
            $validator->errors()->add('unit_ids', 'Select at least one Unit for a Building that trains Units.');

            return;
        }

        if ($unitsPerLevel !== 0 && $this->integer('only_at_level') !== 0) {
            $validator->errors()->add('units_per_level', 'Units cannot be recruited both per level and at a single level. Choose one.');

            return;
        }

        $maxLevel = $this->integer('max_level');
        $generatedMaxLevel = 1 + (($unitCount - 1) * $unitsPerLevel);

        if ($unitsPerLevel > 0 && $generatedMaxLevel > $maxLevel) {
            $validator->errors()->add('units_per_level', 'The generated Unit recruitment level cannot exceed the Building max level.');

            return;
        }

        if ($this->integer('only_at_level') > $maxLevel) {
            $validator->errors()->add('only_at_level', 'The Unit recruitment level cannot exceed the Building max level.');
        }
    }
}
