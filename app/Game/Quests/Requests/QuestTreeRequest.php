<?php

namespace App\Game\Quests\Requests;

use App\Game\Quests\Values\QuestKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestTreeRequest extends FormRequest
{
    /**
     * Determine whether the Quest tree request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the Quest tree validation rules.
     */
    public function rules(): array
    {
        return [
            'map_id' => 'nullable|integer|exists:game_maps,id',
            'kind' => ['nullable', 'string', Rule::enum(QuestKind::class)],
        ];
    }

    /**
     * Resolve the validated Game Map filter.
     */
    public function mapId(): ?int
    {
        if (is_null($this->validated('map_id'))) {
            return null;
        }

        return $this->integer('map_id');
    }

    /**
     * Resolve the validated Quest kind filter.
     */
    public function kind(): ?QuestKind
    {
        $value = $this->validated('kind');

        return is_null($value) ? null : QuestKind::from($value);
    }
}
