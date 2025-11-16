<?php

namespace App\Admin\Services;

use App\Flare\Models\GuideQuest;
use Illuminate\Database\Eloquent\Model;

class GuideQuestService
{
    /**
     * @param ImageHandlerService $imageHandlerService
     */
    public function __construct(private readonly ImageHandlerService $imageHandlerService)
    {
    }

    /**
     * Upsert a GuideQuest from the payload and return the refreshed model.
     *
     * @param array $payload
     * @param GuideQuest $guideQuest
     * @return GuideQuest
     */
    public function upsert(array $payload, GuideQuest $guideQuest): GuideQuest
    {
        $guideQuestId = $payload['guide_quest_id'];
        $hasImage = $payload['has_image'] === '1';
        $incomingContent = $payload['content'];

        $model = $this->resolveModel($guideQuestId, $guideQuest);

        $existingContent = collect($incomingContent)
            ->mapWithKeys(function ($_, $key) use ($model) {
                return [$key => $model->getAttribute($key)];
            })
            ->toArray();

        $finalContent = $this->imageHandlerService->process(
            $model,
            $incomingContent,
            $existingContent,
            $hasImage,
            'guide-quest-images',
            'guide-quests'
        );

        $model->fill($finalContent);
        $model->save();

        return $model->fresh();
    }

    /**
     * @param string $id
     * @param Model $model
     * @return GuideQuest
     */
    private function resolveModel(string $id, Model $model): Model
    {
        $foundInstance = $model::find($id);

        if (is_null($foundInstance)) {
            return $model;
        }

        return $foundInstance;
    }
}
