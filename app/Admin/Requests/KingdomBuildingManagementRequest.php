<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class KingdomBuildingManagementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required',
            'description' => 'required',
            'max_level' => 'required',
            'base_durability' => 'required',
            'base_defence' => 'required',
            'required_population' => 'required',
            'units_per_level' => 'nullable',
            'only_at_level' => 'nullable',
            'is_resource_building' => 'nullable',
            'trains_units' => 'nullable',
            'is_walls' => 'nullable',
            'is_church' => 'nullable',
            'is_farm' => 'nullable',
            'wood_cost' => 'required',
            'clay_cost' => 'required',
            'stone_cost' => 'required',
            'iron_cost' => 'required',
            'time_to_build' => 'required',
            'time_increase_amount' => 'required',
            'decrease_morale_amount' => 'nullable',
            'increase_population_amount' => 'nullable',
            'increase_morale_amount' => 'nullable',
            'increase_wood_amount' => 'nullable',
            'increase_clay_amount' => 'nullable',
            'increase_stone_amount' => 'nullable',
            'increase_iron_amount' => 'nullable',
            'increase_durability_amount' => 'nullable',
            'increase_defence_amount' => 'nullable',
            'is_locked' => 'nullable',
            'passive_skill_id' => 'nullable',
            'level_required' => 'nullable',
        ];
    }
}
