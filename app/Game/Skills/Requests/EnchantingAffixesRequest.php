<?php

namespace App\Game\Skills\Requests;

use App\Flare\Pagination\Requests\PaginationRequest;

class EnchantingAffixesRequest extends PaginationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'type' => 'required|string|in:prefix,suffix',
        ]);
    }
}
