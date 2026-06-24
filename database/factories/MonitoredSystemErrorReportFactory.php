<?php

namespace Database\Factories;

use App\Flare\Models\MonitoredSystemErrorReport;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoredSystemErrorReportFactory extends Factory
{
    protected $model = MonitoredSystemErrorReport::class;

    public function definition(): array
    {
        return [
            'fingerprint' => md5($this->faker->uuid()),
            'title' => $this->faker->sentence(4),
            'status' => 'open',
            'severity' => 'error',
            'environment' => 'testing',
            'exception_class' => 'RuntimeException',
            'latest_message' => $this->faker->sentence(),
            'latest_stack_trace' => null,
            'latest_raw_log_entry' => null,
            'occurrence_count' => 0,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ];
    }
}
