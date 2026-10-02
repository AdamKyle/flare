<?php

namespace App\Flare\Models;

use App\Game\GuideQuests\Values\GuideQuestWalkthroughEvent;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughStepType;
use App\Game\GuideQuests\Values\GuideQuestWalkthroughTarget;
use Database\Factories\GuideQuestWalkthroughStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuideQuestWalkthroughStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'guide_quest_id',
        'position',
        'title',
        'content',
        'target',
        'step_type',
        'required_event',
        'optional_if_target_missing',
    ];

    protected $casts = [
        'guide_quest_id' => 'integer',
        'position' => 'integer',
        'target' => GuideQuestWalkthroughTarget::class,
        'step_type' => GuideQuestWalkthroughStepType::class,
        'required_event' => GuideQuestWalkthroughEvent::class,
        'optional_if_target_missing' => 'boolean',
    ];

    /**
     * Return the Guide Quest that owns this walkthrough step.
     *
     * @return BelongsTo
     */
    public function guideQuest(): BelongsTo
    {
        return $this->belongsTo(GuideQuest::class);
    }

    /**
     * Create the model's factory.
     *
     * @return GuideQuestWalkthroughStepFactory
     */
    protected static function newFactory(): GuideQuestWalkthroughStepFactory
    {
        return GuideQuestWalkthroughStepFactory::new();
    }
}
