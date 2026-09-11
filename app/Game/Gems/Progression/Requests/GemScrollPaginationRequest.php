<?php

namespace App\Game\Gems\Progression\Requests;

use App\Flare\Pagination\Requests\PaginationRequest;

class GemScrollPaginationRequest extends PaginationRequest
{
    /**
     * @return array
     */
    public function rules()
    {
        return [
            'per_page' => 'required|min:1|max:50|integer',
            'page' => 'required|min:1|integer',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'per_page' => $this->input('per_page') ?? 10,
            'page' => $this->input('page') ?? 1,
        ]);
    }
}
