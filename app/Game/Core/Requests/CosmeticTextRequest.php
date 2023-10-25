<?php

namespace App\Game\Core\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CosmeticTextRequest extends FormRequest {
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize() {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules() {
        return [
            'chat_text_color' => 'nullable|string|in:ocean-depths,memeories-grass,depths-despair,lipstick,fifties-cheeks,sky-clouds,golden-sheen',
            'chat_is_bold'    => 'nullable',
            'chat_is_italic'  => 'nullable',
        ];
    }

    public function messages() {
        return [];
    }
}
