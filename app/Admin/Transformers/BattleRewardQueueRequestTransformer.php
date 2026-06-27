<?php

namespace App\Admin\Transformers;

use App\Flare\Models\CharacterBattleRewardRequest;
use App\Flare\Transformers\BaseTransformer;
use App\Game\BattleRewardProcessing\Enums\BattleRewardStepStatus;

class BattleRewardQueueRequestTransformer extends BaseTransformer
{
    public function transformArray(array $request): array
    {
        return [
            'id' => $request['id'],
            'character' => $request['character'],
            'status' => $request['status'],
            'priority' => $request['priority'],
            'source_type' => $request['source_type'],
            'source_id' => $request['source_id'],
            'failed_reason' => $request['failed_reason'],
            'created_at' => $request['created_at'],
            'updated_at' => $request['updated_at'],
            'current_step_name' => $request['current_step_name'],
            'current_step_status' => $request['current_step_status'],
            'completed_step_count' => (int) $request['completed_step_count'],
            'total_step_count' => (int) $request['total_step_count'],
            'un_emitted_message_count' => (int) $request['un_emitted_message_count'],
        ];
    }

    public function transform(CharacterBattleRewardRequest $request): array
    {
        $currentStep = $request->relationLoaded('steps')
            ? $request->steps->first(fn ($step): bool => $step->status !== BattleRewardStepStatus::COMPLETED)
            : null;

        return [
            'id' => $request->id,
            'character' => $request->relationLoaded('character') && ! is_null($request->character)
                ? ['name' => $request->character->name]
                : null,
            'status' => $request->status?->value,
            'priority' => $request->priority?->value,
            'source_type' => $request->source_type?->value,
            'source_id' => $request->source_id,
            'failed_reason' => $request->failed_reason,
            'created_at' => $request->created_at,
            'updated_at' => $request->updated_at,
            'current_step_name' => $currentStep?->step_name?->value,
            'current_step_status' => $currentStep?->status?->value,
            'completed_step_count' => (int) ($request->completed_step_count ?? 0),
            'total_step_count' => (int) ($request->total_step_count ?? 0),
            'un_emitted_message_count' => (int) ($request->un_emitted_message_count ?? 0),
        ];
    }
}
