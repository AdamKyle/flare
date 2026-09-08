<?php

namespace App\Admin\Locations\Requests;

use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationIndexRequest extends FormRequest
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
            'sort_key' => 'required|string|in:name,x,y,type',
            'sort_direction' => 'required|string|in:asc,desc',
            'filters' => 'nullable|array',
            'filters.game_map_id' => 'nullable|integer|exists:game_maps,id',
            'filters.type' => ['nullable', 'integer', Rule::enum(LocationType::class)],
        ];
    }

    /**
     * Apply the Locations list defaults before validation runs.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page', 15),
            'page' => $this->input('page', 1),
            'search_text' => $this->input('search_text', ''),
            'sort_key' => $this->input('sort_key', 'name'),
            'sort_direction' => $this->input('sort_direction', 'asc'),
            'filters' => $this->input('filters', []),
        ]);
    }
}
