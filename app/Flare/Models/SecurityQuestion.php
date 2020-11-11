<?php

namespace App\Flare\Models;

use Database\Factories\SecurityQuestionFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SecurityQuestion extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'question',
        'answer',
    ];

    public function user() {
        return $this->belongsTo(User::class);
    }

    protected static function newFactory() {
        return SecurityQuestionFactory::new();
    }
}
