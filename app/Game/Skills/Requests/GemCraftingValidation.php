<?php

namespace App\Game\Skills\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GemCraftingValidation extends FormRequest {

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
            'tier' => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'tier.required' => 'What tier do you want to craft for?',
            'tier.integer'  => 'Tier must be an integer',
        ];
    }
}
