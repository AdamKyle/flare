<?php

namespace App\Admin\Quests\Requests;

use App\Game\Quests\Values\QuestKind;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QuestTreeRequest extends FormRequest
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
            'map_id' => 'nullable|integer|exists:game_maps,id',
            'kind' => ['nullable', 'string', Rule::enum(QuestKind::class)],
        ];
    }

    /**
     * Resolve the validated Game Map filter.
     *
     * @return int|null Validated Game Map id filter.
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
     *
     * @return QuestKind|null Validated Quest kind filter.
     */
    public function kind(): ?QuestKind
    {
        $value = $this->validated('kind');

        return is_null($value) ? null : QuestKind::from($value);
    }
}
