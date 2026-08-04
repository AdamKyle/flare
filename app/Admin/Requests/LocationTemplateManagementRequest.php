<?php

namespace App\Admin\Requests;

use App\Game\Maps\Values\LocationTemplateType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationTemplateManagementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['nullable', 'integer'],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('location_templates', 'name')->ignore($this->integer('id')),
            ],
            'description' => [
                'required',
                'string',
                Rule::unique('location_templates', 'description')->ignore($this->integer('id')),
            ],
            'type' => ['required', 'string', Rule::in(array_keys(LocationTemplateType::getNamedValues()))],
            'can_players_enter' => ['nullable', 'boolean'],
        ];
    }
}
