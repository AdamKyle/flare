<?php

namespace App\Admin\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUnitRequest extends FormRequest
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
     * Return the validation rules for creating or updating a Kingdom Unit.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'attack' => 'required|integer',
            'defence' => 'required|integer',
            'can_heal' => 'required|boolean',
            'heal_percentage' => 'nullable|numeric',
            'siege_weapon' => 'required|boolean',
            'is_airship' => 'required|boolean',
            'attacker' => 'required|boolean',
            'defender' => 'required|boolean',
            'can_not_be_healed' => 'required|boolean',
            'is_settler' => 'required|boolean',
            'is_special' => 'required|boolean',
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

    /**
     * Return the Kingdom Unit validation messages.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Unit name.',
            'description.required' => 'Enter a Unit description.',
            'attack.required' => 'Enter the Unit attack.',
            'defence.required' => 'Enter the Unit defence.',
            'time_to_recruit.required' => 'Enter the Unit recruitment time.',
        ];
    }
}
