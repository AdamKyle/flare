<?php

namespace App\Flare\Models;

use Database\Factories\CharacterGuideQuestProgressFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CharacterGuideQuestProgress extends Model
{
    use HasFactory;

    protected $fillable = [
        'character_id',
        'guide_quest_id',
        'current_guide_quest_walkthrough_step_id',
        'started_at',
        'walkthrough_completed_at',
        'hand_in_ready_at',
        'attention_acknowledged_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'walkthrough_completed_at' => 'datetime',
        'hand_in_ready_at' => 'datetime',
        'attention_acknowledged_at' => 'datetime',
    ];

    /**
     * Return the Character that owns this progress record.
     *
     * @return mixed
     */
    public function character()
    {
        return $this->belongsTo(Character::class);
    }

    /**
     * Return the Guide Quest represented by this progress record.
     *
     * @return mixed
     */
    public function guideQuest()
    {
        return $this->belongsTo(GuideQuest::class);
    }

    /**
     * Return the persisted current walkthrough step.
     *
     * @return mixed
     */
    public function currentStep()
    {
        return $this->belongsTo(GuideQuestWalkthroughStep::class, 'current_guide_quest_walkthrough_step_id');
    }

    /**
     * Create the model's factory.
     *
     * @return CharacterGuideQuestProgressFactory
     */
    protected static function newFactory(): CharacterGuideQuestProgressFactory
    {
        return CharacterGuideQuestProgressFactory::new();
    }
}
