<?php

namespace App\Flare\Models;

use Database\Factories\TopsMonthlySnapshotFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TopsMonthlySnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_type',
        'metric_key',
        'period_start',
        'period_end',
        'rank',
        'character_id',
        'subject_type',
        'subject_id',
        'score_integer',
        'score_decimal',
        'snapshot_data',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'rank' => 'integer',
        'character_id' => 'integer',
        'subject_id' => 'integer',
        'score_integer' => 'integer',
        'score_decimal' => 'float',
        'snapshot_data' => 'array',
    ];

    protected static function newFactory(): TopsMonthlySnapshotFactory
    {
        return TopsMonthlySnapshotFactory::new();
    }
}
