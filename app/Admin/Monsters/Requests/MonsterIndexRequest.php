<?php

namespace App\Admin\Monsters\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MonsterIndexRequest extends FormRequest
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
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'per_page' => 'required|integer|min:1|max:100',
            'page' => 'required|integer|min:1',
            'search_text' => 'nullable|string|max:255',
            'sort_key' => 'required|string|in:name,xp,gold,max_level',
            'sort_direction' => 'required|string|in:asc,desc',
            'filters' => 'nullable|array',
            'filters.game_map_id' => 'nullable|integer|exists:game_maps,id',
        ];
    }

    /**
     * Apply the Monsters list defaults before validation runs.
     *
     * @return void Merges default list parameters into the request input.
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
