<?php

namespace App\Info\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonsterGemEffectContextIndexRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1|max:50',
            'page' => 'required|integer|min:1',
        ];
    }

    /**
     * Apply the Monster Gem effect context list defaults before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 10),
            'page' => $this->input('page', 1),
        ]);
    }
}
