<?php

namespace App\Flare\Models;

use Database\Factories\GuideQuestWalkthroughStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'position' => 'integer',
        'optional_if_target_missing' => 'boolean',
    ];

    /**
     * Return the Guide Quest that owns this walkthrough step.
     *
     * @return mixed
     */
    public function guideQuest()
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
