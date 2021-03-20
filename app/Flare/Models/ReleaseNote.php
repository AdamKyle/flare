<?php

namespace App\Flare\Models;

use Database\Factories\ReleaseNoteFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ReleaseNote extends Model {

    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'url',
        'release_date',
        'body',
    ];

    protected static function newFactory() {
        return ReleaseNoteFactory::new();
    }
}
