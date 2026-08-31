<?php

namespace App\Admin\GameMaps\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GameMapRelationIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool Always true; authorization is enforced by route middleware.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1|max:50',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
        ];
    }

    /**
     * Apply the Game Map relationship list defaults before validation runs.
     *
     * @return void Merges default list parameters into the request input.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 10),
            'page' => $this->input('page', 1),
            'search_text' => $this->input('search_text'),
        ]);
    }
}
