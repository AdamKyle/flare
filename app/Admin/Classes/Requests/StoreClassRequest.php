<?php

namespace App\Admin\Classes\Requests;

use App\Game\ClassRanks\Values\ClassRankValue;
use App\Game\Core\Values\CoreStatType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreClassRequest extends FormRequest
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
     * @return array<string,mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'damage_stat' => ['required', 'string', Rule::enum(CoreStatType::class)],
            'to_hit_stat' => ['required', 'string', Rule::enum(CoreStatType::class)],
            'str_mod' => 'required|integer',
            'dur_mod' => 'required|integer',
            'dex_mod' => 'required|integer',
            'chr_mod' => 'required|integer',
            'int_mod' => 'required|integer',
            'agi_mod' => 'required|integer',
            'focus_mod' => 'required|integer',
            'accuracy_mod' => 'required|numeric',
            'dodge_mod' => 'required|numeric',
            'defense_mod' => 'required|numeric',
            'looting_mod' => 'required|numeric',
            'primary_required_class_id' => ['nullable', 'integer', Rule::exists('game_classes', 'id')],
            'secondary_required_class_id' => ['nullable', 'integer', Rule::exists('game_classes', 'id')],
            'primary_required_class_level' => ['nullable', 'integer', 'min:1', 'max:'.ClassRankValue::MAX_LEVEL],
            'secondary_required_class_level' => ['nullable', 'integer', 'min:1', 'max:'.ClassRankValue::MAX_LEVEL],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string,string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a Class name.',
            'damage_stat.required' => 'Select a damage stat.',
            'to_hit_stat.required' => 'Select a to-hit stat.',
        ];
    }

    /**
     * Configure the validator instance to enforce Class unlock requirement rules.
     *
     * @param  Validator  $validator  Validator instance to configure.
     * @return void Registers the unlock-requirement validation callback.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $this->validateUnlockRequirements($validator);
        });
    }

    /**
     * Enforce that unlock requirements are either fully absent or fully populated, that the two
     * prerequisite Classes differ, and that a Class cannot require itself.
     *
     * @param  Validator  $validator  Validator instance to add errors to.
     * @return void Validation failures are registered through the supplied validator.
     */
    private function validateUnlockRequirements(Validator $validator): void
    {
        $fields = [
            'primary_required_class_id',
            'secondary_required_class_id',
            'primary_required_class_level',
            'secondary_required_class_level',
        ];

        $populatedCount = collect($fields)->filter(fn (string $field) => ! is_null($this->input($field)))->count();

        if ($populatedCount > 0 && $populatedCount < count($fields)) {
            $validator->errors()->add('primary_required_class_id', 'A special Class requires both prerequisite Classes and both required levels, or none of them.');

            return;
        }

        $primaryClassId = $this->input('primary_required_class_id');
        $secondaryClassId = $this->input('secondary_required_class_id');

        if (is_null($primaryClassId) || is_null($secondaryClassId)) {
            return;
        }

        if ($primaryClassId === $secondaryClassId) {
            $validator->errors()->add('secondary_required_class_id', 'The primary and secondary prerequisite Classes must be different.');
        }

        $editingClassId = $this->route('gameClass')?->id;

        if (! is_null($editingClassId) && ($primaryClassId === $editingClassId || $secondaryClassId === $editingClassId)) {
            $validator->errors()->add('primary_required_class_id', 'A Class cannot require itself.');
        }
    }
}
