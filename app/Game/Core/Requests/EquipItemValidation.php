<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EquipItemValidation extends FormRequest
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
            'position'           => 'required|in:left-hand,right-hand,body,shield,legs,hands,sleeves,head,gloves,ring_one,ring_two,spell_one,spell_two,artifact_one,artifact_two',
            'slot_id'            => 'required',
            'equip_type'         => 'required|in:weapon,body,shield,leggings,sleeves,helmet,gloves,ring,spell-healing,spell-damage,artifact'
        ];
    }

    public function messages() {
        return [
            'position.required'   => 'Error. Invalid Input.',
            'position.in'         => 'Error. Invalid Input.',
            'slot_id.required'    => 'Error. Invalid Input.',
            'equip_type.required' => 'Error. Invalid Input.',
            'equip_type.in'       => 'Error. Invalid Input.',
        ];
    }
}