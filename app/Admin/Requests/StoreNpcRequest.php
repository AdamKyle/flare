<?php

namespace App\Admin\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreNpcRequest extends FormRequest
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
            'npc_id' => 'nullable|integer|exists:npcs,id',
            'real_name' => 'required|string',
            'game_map_id' => 'required|integer|exists:game_maps,id',
            'type' => 'required|integer',
            'x_position' => 'required|integer',
            'y_position' => 'required|integer',
        ];
    }

    /**
     * Messages for the validation.
     */
    public function messages(): array
    {
        return [
            'real_name.required' => 'Npc name is required',
            'game_map_id.required' => 'Game map must be selected',
            'type.required' => 'Npc type must be selected',
            'x_position.required' => 'X position must be selected',
            'y_position.required' => 'Y position must be selected',
        ];
    }
}
