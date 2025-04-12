<?php

namespace App\Game\Character\CharacterInventory\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InventoryPaginationRequest extends FormRequest
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
            'per_page' => 'required|min:1|integer',
            'page' => 'required|min:1|integer',
            'search_text' => 'nullable|string',
        ];
    }

    public function messages()
    {
        return [
            'per_page.required' => 'How many do you want per page?',
            'page.required' => 'What page are we trying to fetch?',
            'search_text.required' => 'What are you searching for?',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'search_text' => $this->has('search_text') && is_null($this->input('search_text'))
                ? ''
                : $this->input('search_text'),
        ]);
    }

}
