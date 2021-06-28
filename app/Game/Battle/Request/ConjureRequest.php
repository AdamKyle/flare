<?php

namespace App\Game\Battle\Request;

use Illuminate\Foundation\Http\FormRequest;

class ConjureRequest extends FormRequest
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
            'monster_id'      => 'required|integer',
            'type'            => 'required|in:public,private',
        ];
    }

    public function messages() {
        return [
            'monster_id.required'    => 'What monster are you trying to conjure?',
            'type.required'          => 'Missing type.',
            'type.in'                => 'Invalid input.',
        ];
    }
}
