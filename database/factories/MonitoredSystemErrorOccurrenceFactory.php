<?php

namespace Database\Factories;

use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoredSystemErrorOccurrenceFactory extends Factory
{
    protected $model = MonitoredSystemErrorOccurrence::class;

    public function definition(): array
    {
        return [
            'monitored_system_error_report_id' => MonitoredSystemErrorReport::factory(),
            'occurred_at' => now(),
            'level' => 'error',
            'channel' => 'local',
            'file_path' => 'logs/laravel.log',
            'message' => $this->faker->sentence(),
            'exception_class' => 'RuntimeException',
            'exception_file' => null,
            'exception_line' => null,
            'stack_trace' => null,
            'raw_log_entry' => null,
            'user_id' => null,
            'request_path' => null,
            'job_class' => null,
            'queue' => null,
            'environment' => 'testing',
            'context' => [],
        ];
    }
}
