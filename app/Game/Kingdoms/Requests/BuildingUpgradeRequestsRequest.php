<?php

namespace App\Game\Kingdoms\Requests;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomBuilding;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BuildingUpgradeRequestsRequest extends FormRequest
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
            'request_data' => 'required|array',
            'request_data.*.kingdomId' => 'required|integer',
            'request_data.*.buildingIds' => 'required|array',
            'request_data.*.buildingIds.*' => 'integer',
            'request_type' => 'required|in:upgrade,repair',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($this->input('request_type') !== 'upgrade') {
                return;
            }

            foreach ($this->input('request_data', []) as $request) {
                if (! is_array($request) || ! array_key_exists('kingdomId', $request) || ! array_key_exists('buildingIds', $request)) {
                    continue;
                }

                $buildings = KingdomBuilding::query()
                    ->where('kingdom_id', $request['kingdomId'])
                    ->whereIn('id', $request['buildingIds'])
                    ->with('gameBuilding')
                    ->get();

                if ($buildings->contains(fn(KingdomBuilding $building) => $building->level >= $building->gameBuilding->max_level)) {
                    $validator->errors()->add('request_data', 'One or more buildings are already max level.');
                }

                if ($this->hasActiveManualUpgradeQueue((int) $request['kingdomId'], $request['buildingIds']) ||
                    $this->hasActiveCapitalCityUpgradeQueue((int) $request['kingdomId'], $request['buildingIds'])
                ) {
                    $validator->errors()->add('request_data', 'One or more buildings are already queued for upgrade.');
                }
            }
        });
    }

    private function hasActiveManualUpgradeQueue(int $kingdomId, array $buildingIds): bool
    {
        return BuildingInQueue::query()
            ->where('kingdom_id', $kingdomId)
            ->whereIn('building_id', $buildingIds)
            ->where('type', BuildingQueueType::UPGRADE)
            ->exists();
    }

    private function hasActiveCapitalCityUpgradeQueue(int $kingdomId, array $buildingIds): bool
    {
        return CapitalCityBuildingQueue::query()
            ->where('kingdom_id', $kingdomId)
            ->whereNotIn('status', [
                CapitalCityQueueStatus::REJECTED,
                CapitalCityQueueStatus::FINISHED,
                CapitalCityQueueStatus::CANCELLED,
            ])
            ->get()
            ->contains(function (CapitalCityBuildingQueue $queue) use ($buildingIds) {
                return collect($queue->building_request_data)
                    ->contains(function (array $request) use ($buildingIds) {
                        return $request['type'] === 'upgrade' &&
                            in_array((int) $request['building_id'], $buildingIds, true) &&
                            ! in_array($request['secondary_status'], [
                                CapitalCityQueueStatus::REJECTED,
                                CapitalCityQueueStatus::FINISHED,
                                CapitalCityQueueStatus::CANCELLED,
                            ], true);
                    });
            });
    }

    public function messages()
    {
        return [
            'request_data.required' => 'Missing request data',
            'request_type.required' => 'Missing request type',
        ];
    }
}
