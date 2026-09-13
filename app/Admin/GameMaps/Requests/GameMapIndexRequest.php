<?php

namespace App\Admin\GameMaps\Requests;

use App\Admin\GameMaps\Values\AdminGameMapType;
use App\Game\Maps\Values\MapName;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GameMapIndexRequest extends FormRequest
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
            'per_page' => 'required|integer|min:1|max:100',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
            'sort_key' => 'required|string|in:name',
            'sort_direction' => 'required|string|in:asc,desc',
            'plane' => ['nullable', 'string', Rule::enum(MapName::class)],
            'map_type' => ['nullable', 'string', Rule::enum(AdminGameMapType::class)],
        ];
    }

    /**
     * Apply the Game Maps list defaults before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
            'search_text' => $this->input('search_text', ''),
            'sort_key' => $this->input('sort_key', 'name'),
            'sort_direction' => $this->input('sort_direction', 'asc'),
            'plane' => $this->input('plane') ?: null,
            'map_type' => $this->input('map_type') ?: null,
        ]);
    }
}
