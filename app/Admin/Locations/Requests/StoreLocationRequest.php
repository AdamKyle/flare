<?php

namespace App\Admin\Locations\Requests;

use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLocationRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'quest_reward_item_id' => 'nullable|integer|exists:items,id',
            'required_quest_item_id' => 'nullable|integer|exists:items,id',
            'is_port' => 'required|boolean',
            'can_players_enter' => 'required|boolean',
            'can_auto_battle' => 'required|boolean',
            'x' => 'required|integer',
            'y' => 'required|integer',
            'type' => ['nullable', 'integer', Rule::enum(LocationType::class)],
            'pin_css_class' => 'nullable|string|in:christmas-tree-x-pin,snowman-x-pin',
            'hours_to_drop' => 'nullable|integer|min:0',
            'minutes_between_delve_fights' => 'nullable|integer|min:0',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Location name.',
            'name.max' => 'The Location name may not be longer than 255 characters.',
            'description.required' => 'Enter a Location description.',
            'quest_reward_item_id.exists' => 'The selected quest reward Item does not exist.',
            'required_quest_item_id.exists' => 'The selected required quest Item does not exist.',
            'is_port.required' => 'Select whether this Location is a port.',
            'can_players_enter.required' => 'Select whether players can enter this Location.',
            'can_auto_battle.required' => 'Select whether this Location supports auto battle.',
            'x.required' => 'Select an X coordinate for this Location.',
            'y.required' => 'Select a Y coordinate for this Location.',
            'type.integer' => 'The selected Location type is invalid.',
            'pin_css_class.in' => 'The selected map pin is invalid.',
            'hours_to_drop.min' => 'Hours until quest-item drop must be zero or greater.',
            'minutes_between_delve_fights.min' => 'Minutes between Delve fights must be zero or greater.',
        ];
    }
}
