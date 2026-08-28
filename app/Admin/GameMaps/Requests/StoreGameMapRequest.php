<?php

namespace App\Admin\GameMaps\Requests;

use App\Game\Events\Values\EventType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGameMapRequest extends FormRequest
{
    /**
     * Allow authorized Admin routing to submit a Game Map creation request.
     *
     * @return bool Whether the request is authorized.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Return the validation rules for creating a Game Map.
     *
     * @return array<string, mixed> Game Map creation validation rules.
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'kingdom_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'default' => 'required|boolean',
            'can_traverse' => 'required|boolean',
            'only_during_event_type' => [
                'nullable',
                'integer',
                Rule::in(array_keys(EventType::getOptionsForSelect())),
            ],
            'xp_bonus' => 'required|numeric',
            'skill_training_bonus' => 'required|numeric',
            'drop_chance_bonus' => 'required|numeric',
            'enemy_stat_bonus' => 'required|numeric',
            'character_attack_reduction' => 'required|numeric',
            'required_location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'map' => 'required|image|max:2000',
        ];
    }

    /**
     * Return the custom validation messages for Game Map creation.
     *
     * @return array<string, string> Game Map creation validation messages.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Game Map name.',
            'name.max' => 'The Game Map name may not be longer than 255 characters.',
            'kingdom_color.required' => 'Select a Kingdom color for this Game Map.',
            'kingdom_color.regex' => 'Enter a Kingdom color as a # followed by exactly six hexadecimal characters.',
            'default.required' => 'Select whether this is the default Game Map.',
            'can_traverse.required' => 'Select whether this Game Map can be traversed.',
            'xp_bonus.required' => 'Enter an XP Bonus for this Game Map.',
            'xp_bonus.numeric' => 'The XP Bonus must be a number.',
            'skill_training_bonus.required' => 'Enter a Skill Training Bonus for this Game Map.',
            'skill_training_bonus.numeric' => 'The Skill Training Bonus must be a number.',
            'drop_chance_bonus.required' => 'Enter a Drop Chance Bonus for this Game Map.',
            'drop_chance_bonus.numeric' => 'The Drop Chance Bonus must be a number.',
            'enemy_stat_bonus.required' => 'Enter an Enemy Stat Increase for this Game Map.',
            'enemy_stat_bonus.numeric' => 'The Enemy Stat Increase must be a number.',
            'character_attack_reduction.required' => 'Enter a Character Attack Reduction for this Game Map.',
            'character_attack_reduction.numeric' => 'The Character Attack Reduction must be a number.',
            'required_location_id.exists' => 'The selected required Location does not exist.',
            'map.required' => 'Upload a map image for this Game Map.',
            'map.image' => 'The map upload must be an image.',
            'map.max' => 'The map image may not be larger than 2MB.',
        ];
    }

    /**
     * Normalize present multipart boolean fields before validation.
     *
     * @return void The request input is normalized in place.
     */
    protected function prepareForValidation(): void
    {
        $normalizedBooleans = [];

        if ($this->has('default')) {
            $normalizedBooleans['default'] = $this->boolean('default');
        }

        if ($this->has('can_traverse')) {
            $normalizedBooleans['can_traverse'] = $this->boolean('can_traverse');
        }

        if (! $this->has('only_during_event_type')) {
            $normalizedBooleans['only_during_event_type'] = null;
        }

        $this->merge($normalizedBooleans);
    }
}
