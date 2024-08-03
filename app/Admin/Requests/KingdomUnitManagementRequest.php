<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class KingdomUnitManagementRequest extends FormRequest
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
            'id' => 'nullable|integer',
            'name' => 'required',
            'description' => 'required',
            'attack' => 'required|integer',
            'defence' => 'required|integer',
            'can_heal' => 'nullable|boolean',
            'heal_percentage' => 'nullable|numeric',
            'unit_can_heal' => 'nullable|boolean',
            'siege_weapon' => 'nullable|boolean',
            'is_airship' => 'nullable|boolean',
            'attacker' => 'nullable|boolean',
            'defender' => 'nullable|boolean',
            'can_not_be_healed' => 'nullable|boolean',
            'is_settler' => 'nullable|boolean',
            'reduces_morale_by' => 'nullable|numeric',
            'wood_cost' => 'nullable|integer',
            'clay_cost' => 'nullable|integer',
            'stone_cost' => 'nullable|integer',
            'iron_cost' => 'nullable|integer',
            'steel_cost' => 'nullable|integer',
            'required_population' => 'nullable|integer',
            'time_to_recruit' => 'required|integer',
        ];
    }
}
