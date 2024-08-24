<?php

namespace App\Game\NpcActions\LabyrinthOracle\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemTransferRequest extends FormRequest
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
            'item_id_from' => 'required|integer',
            'item_id_to' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'item_id_from.required' => 'Missing item to transfer from.',
            'item_id_to.required' => 'Missing item to transfer to.',
        ];
    }
}
