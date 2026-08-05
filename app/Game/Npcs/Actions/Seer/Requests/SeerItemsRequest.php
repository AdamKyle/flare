<?php

namespace App\Game\Npcs\Actions\Seer\Requests;

use App\Flare\Pagination\Requests\PaginationRequest;

class SeerItemsRequest extends PaginationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'purpose' => 'required|string|in:sockets,attach',
        ]);
    }
}
