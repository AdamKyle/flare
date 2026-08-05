<?php

namespace App\Game\Skills\Requests;

use App\Flare\Pagination\Requests\PaginationRequest;

class EnchantingItemsRequest extends PaginationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'source' => 'required|string|in:regular,event',
        ]);
    }

    public function prepareForValidation(): void
    {
        parent::prepareForValidation();

        $this->merge([
            'source' => $this->input('source') ?? 'regular',
        ]);
    }
}
