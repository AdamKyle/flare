<?php

namespace App\Game\Kingdoms\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MoveUnitsRequest extends FormRequest
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
            'units_to_move' => 'required|array',
        ];
    }

    public function messages()
    {
        return [
            'units_to_move.required' => 'Units to move are required',
            'units_to_move.*.unit_id' => 'At least one unit must be selected',
            'units_to_move.*.kingdom_id' => 'Missing kingdom this unit is from',
            'units_to_move.*.amount' => 'Missing amount of units to move',
        ];
    }
}
