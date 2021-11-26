<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * @codeCoverageIgnore
 */
class ManagePassiveSkillRequest extends FormRequest
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
            'name'                 => 'required|string',
            'description'          => 'required|string',
            'max_level'            => 'required|integer',
            'bonus_per_level'      => 'required',
            'effect_type'          => 'required|integer',
            'hours_per_level'      => 'required|integer',
            'parent_skill_id'      => 'nullable|integer',
            'unlocks_at_level'     => 'nullable|integer',
            'is_locked'            => 'nullable',
            'is_parent'            => 'nullable',
        ];
    }

    /**
     * Messages for the validation.
     *
     * @return array
     */
    public function messages() {
        return [
            'name.required'                 => 'Missing name.',
            'description.required'          => 'Missing description.',
            'max_level.required'            => 'Missing max level.',
            'bonus_per_level.required'      => 'Missing bonus per level',
            'effect_type.required'          => 'Missing effect type.',
            'hours_per_level.required'      => 'Missing length of time per level.'
        ];
    }
}
