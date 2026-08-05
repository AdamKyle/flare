<?php

namespace App\Game\Npcs\Actions\QueenOfHearts\Requests;

use App\Flare\Pagination\Requests\PaginationRequest;

class QueenDestinationItemsRequest extends PaginationRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return array_merge(parent::rules(), [
            'source_slot_id' => 'required|integer|exists:inventory_slots,id',
        ]);
    }
}
