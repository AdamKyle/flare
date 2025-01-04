<?php

namespace App\Game\Character\CharacterInventory\Requests;

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
            'position' => 'required|in:left-hand,right-hand,body,shield,leggings,feet,sleeves,helmet,gloves,ring-one,ring-two,spell-one,spell-two,trinket,artifact',
            'slot_id' => 'required',
            'equip_type' => 'required|in:artifact,weapon,hammer,bow,gun,fan,mace,scratch-awl,stave,body,shield,leggings,feet,sleeves,helmet,gloves,ring,spell-healing,spell-damage,trinket',
        ];
    }

    public function messages()
    {
        return [
            'position.required' => 'You must select a position for your item',
            'position.in' => 'Error. Invalid Input.',
            'slot_id.required' => 'Error. Invalid Input.',
            'equip_type.required' => 'Error. Invalid Input.',
            'equip_type.in' => 'Error. Invalid Input.',
        ];
    }
}
