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
            'position'           => 'required|in:left-hand,right-hand,body,shield,leggings,feet,sleeves,sleeves,helmet,gloves,ring-one,ring-two,spell-one,spell-two,artifact-one,artifact-two',
            'slot_id'            => 'required',
            'equip_type'         => 'required|in:weapon,body,shield,leggings,feet,sleeves,helmet,gloves,ring,spell-healing,spell-damage,artifact',
            'item_id_to_buy'     => 'nullable|integer|exists:items,id',
        ];
    }

    public function messages() {
        return [
            'position.required'   => 'You must select a position for your item',
            'position.in'         => 'Error. Invalid Input.',
            'slot_id.required'    => 'Error. Invalid Input.',
            'equip_type.required' => 'Error. Invalid Input.',
            'equip_type.in'       => 'Error. Invalid Input.',
        ];
    }
}
