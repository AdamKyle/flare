<?php

namespace App\Admin\Monsters\Requests;

use App\Admin\Monsters\Values\MonsterListCategory;
use App\Game\Maps\Values\LocationType;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class MonsterIndexRequest extends FormRequest
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
            'sort_key' => 'required|string|in:name,xp,gold,max_level',
            'sort_direction' => 'required|string|in:asc,desc',
            'filters' => 'nullable|array',
            'filters.game_map_id' => 'nullable|integer|exists:game_maps,id',
            'filters.category' => 'nullable|string|in:'.implode(',', MonsterListCategory::values()),
            'filters.location_type' => 'nullable|integer|in:'.implode(',', LocationType::values()),
        ];
    }

    /**
     * Cross-field validation tying the Location Type filter to the Monster Category filter.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filters = $this->input('filters', []);
            $category = $filters['category'] ?? null;
            $locationType = $filters['location_type'] ?? null;

            if (is_null($locationType)) {
                return;
            }

            if ($category !== MonsterListCategory::WEEKLY_FIGHT->value) {
                $validator->errors()->add(
                    'filters.location_type',
                    'The Location Type filter is only valid for the Weekly Fight category.'
                );

                return;
            }

            if (! in_array($locationType, MonsterListCategory::weeklyFightLocationTypes(), true)) {
                $validator->errors()->add(
                    'filters.location_type',
                    'The selected Location Type is not part of the Weekly Fight category.'
                );
            }
        });
    }

    /**
     * Apply the Monsters list defaults before validation runs.
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
