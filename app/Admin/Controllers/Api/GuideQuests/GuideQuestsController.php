<?php

namespace App\Admin\Controllers\Api\GuideQuests;

use App\Admin\Transformers\GuideQuestTransformer;
use App\Flare\Models\GuideQuest;
use App\Flare\Transformers\Serializer\PlainDataSerializer;
use Illuminate\Http\JsonResponse;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;

class GuideQuestsController {

    public function __construct(private readonly PlainDataSerializer $plainDataSerializer, private readonly Manager $manager, private readonly GuideQuestTransformer $guideQuestTransformer) {
    }

    /**
     * @param GuideQuest $guideQuest
     * @return JsonResponse
     */
    public function guideQuest(GuideQuest $guideQuest): JsonResponse {

        $guideQuestData = new Item($guideQuest, $this->guideQuestTransformer);
        $guideQuestData = $this->manager->setSerializer($this->plainDataSerializer)->createData($guideQuestData)->toArray();

        return response()->json($guideQuestData);
    }
}