<?php

namespace App\Flare\Models;

use Database\Factories\MonitoredSystemErrorOccurrenceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoredSystemErrorOccurrence extends Model
{
    use HasFactory;

    protected $fillable = [
        'monitored_system_error_report_id',
        'occurred_at',
        'level',
        'channel',
        'file_path',
        'message',
        'exception_class',
        'exception_file',
        'exception_line',
        'stack_trace',
        'raw_log_entry',
        'user_id',
        'request_path',
        'job_class',
        'queue',
        'environment',
        'context',
    ];

    protected $casts = [
        'occurred_at' => 'datetime',
        'exception_line' => 'integer',
        'user_id' => 'integer',
        'context' => 'array',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonitoredSystemErrorReport::class, 'monitored_system_error_report_id');
    }

    protected static function newFactory(): MonitoredSystemErrorOccurrenceFactory
    {
        return MonitoredSystemErrorOccurrenceFactory::new();
    }
}
