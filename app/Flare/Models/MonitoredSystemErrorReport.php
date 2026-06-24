<?php

namespace App\Flare\Models;

use Database\Factories\MonitoredSystemErrorReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MonitoredSystemErrorReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'fingerprint',
        'title',
        'status',
        'severity',
        'environment',
        'exception_class',
        'latest_message',
        'latest_stack_trace',
        'latest_raw_log_entry',
        'occurrence_count',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'occurrence_count' => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public function occurrences(): HasMany
    {
        return $this->hasMany(MonitoredSystemErrorOccurrence::class);
    }

    protected static function newFactory(): MonitoredSystemErrorReportFactory
    {
        return MonitoredSystemErrorReportFactory::new();
    }
}
