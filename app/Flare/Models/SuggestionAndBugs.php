<?php

namespace App\Flare\Models;

use Illuminate\Database\Eloquent\Model;

class SuggestionAndBugs extends Model
{
    protected $table = 'smelting_progress';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'character_id',
        'title',
        'type',
        'platform',
        'description',
        'uploaded_image_paths',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'title' => 'string',
        'type' => 'string',
        'platform' => 'string',
        'description' => 'string',
        'uploaded_image_paths' => 'array',
    ];

    public function character() {
        return $this->belongsTo(Character::class, 'character_id', 'id');
    }
}
