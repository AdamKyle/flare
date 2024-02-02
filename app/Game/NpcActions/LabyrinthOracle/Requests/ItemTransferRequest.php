<?php

namespace App\Game\NpcActions\LabyrinthOracle\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemTransferRequest extends FormRequest
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
            'item_id_from' => 'required|integer',
            'item_id_to' => 'required|integer',
            'currency_costs' => 'required|array',
            'currency_costs.gold' => 'required|integer',
            'currency_costs.gold_dust' => 'required|integer',
            'currency_costs.shards' => 'required|integer',
            'currency_costs.copper_coins' => 'required|integer',
        ];
    }

    public function messages() {
        return [
            'item_id_from.required' => 'Missing item to transfer from.',
            'item_id_to.required' => 'Missing item to transfer to.',
            'currency_costs.required' => 'Missing the currency costs.',
            'currency_costs.gold.required' => 'Must include gold as a currency cost.',
            'currency_costs.gold_dust.required' => 'Must include Gold Dust as a currency cost.',
            'currency_costs.shards.required' => 'Must include Shards as a currency cost.',
            'currency_costs.copper_coins.required' => 'Must include Copper Coins as a currency cost.',
        ];
    }
}
