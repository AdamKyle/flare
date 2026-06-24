<?php

namespace Tests\Feature\Admin;

use App\Admin\Services\AdminLogsDashboardService;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\MonitoredLogFileState;
use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminLogsDashboardTest extends TestCase
{
    use CreateRole, CreateUser, RefreshDatabase;

    public function testNonAdminCannotAccessLogsDashboardPage(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/logs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testAdminCanViewLogsDashboardPage(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/logs');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function testNonAdminCannotAccessLogsFilesApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/logs/files');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testLogFilesApiReturnsWhitelistedFiles(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        $this->assertSame(200, $response->getStatusCode());
        $files = $response->json();
        $this->assertIsArray($files);

        $keys = array_column($files, 'key');
        $this->assertContains('laravel', $keys);
        $this->assertContains('faction_loyalty', $keys);
        $this->assertContains('exploration_automation', $keys);
    }

    public function testLogEntriesApiReturnsEmptyForMissingLogFile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'laravel',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('data', $response->json());
    }

    public function testNonAdminCannotAccessLogEntriesApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'laravel',
        ]);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testLogEntriesApiReturnsEmptyForUnknownFileKey(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'unknown_key_not_whitelisted',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json('data'));
    }

    public function testLogSummaryApiReturnsExpectedKeysForMissingFile(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/summary', [
            'file' => 'laravel',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total', $response->json());
        $this->assertArrayHasKey('by_severity', $response->json());
        $this->assertArrayHasKey('chart', $response->json());
    }

    public function testNonAdminCannotAccessLogSummaryApi(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/logs/summary', [
            'file' => 'laravel',
        ]);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function testLogFilesApiIncludesExistsAndSizeFields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        $files = $response->json();
        $this->assertArrayHasKey('exists', $files[0]);
        $this->assertArrayHasKey('size_bytes', $files[0]);
        $this->assertArrayHasKey('label', $files[0]);
    }

    public function testLogEntriesReturnsNewestLinesFirstWhenFileHasContent(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        $oldLine = '[2026-01-01 00:00:00] local.INFO: Old message';
        $newLine = '[2026-06-23 12:00:00] local.INFO: New message';
        file_put_contents($logPath, $oldLine . "\n" . $newLine . "\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->entries('capital_city', 1, '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertNotEmpty($result['data']);
        $this->assertSame('New message', $result['data'][0]['message']);
    }

    public function testMissingLogFileDoesNotCreateBugReport(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        if ($existed) {
            unlink($logPath);
        }

        $service = $this->app->make(AdminLogsDashboardService::class);
        $service->entries('laravel', 1, '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        }

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testEmptyLogFileDoesNotCreateBugReport(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath, '');

        $service = $this->app->make(AdminLogsDashboardService::class);
        $service->entries('laravel', 1, '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testUnknownFileKeyDoesNotCreateBugReport(): void
    {
        $service = $this->app->make(AdminLogsDashboardService::class);
        $service->entries('unknown_key_not_whitelisted', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testUnreadableLogFileIsTreatedAsMissingBySafeDiscovery(): void
    {
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Cannot test file permission failure as root.');
        }

        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath, "log content\n");
        chmod($logPath, 0000);

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->entries('capital_city', 1, '', '', '');

        chmod($logPath, 0644);

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame([], $result['data']);
        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testDatedLaravelLogsAreDiscovered(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $logPath = storage_path('logs/laravel-2026-06-24.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath, "[2026-06-24 12:00:00] local.INFO: Dated log message\n");

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $laravel = collect($response->json())->firstWhere('key', 'laravel');

        $this->assertTrue($laravel['exists']);
        $this->assertContains('laravel-2026-06-24.log', $laravel['files']);
    }

    public function testChannelIsNotMissingWhenMatchingDatedFileExists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $logPath = storage_path('logs/exploration-automation-2026-06-24.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath, "[2026-06-24 12:00:00] local.INFO: Exploration dated log\n");

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $exploration = collect($response->json())->firstWhere('key', 'exploration_automation');

        $this->assertTrue($exploration['exists']);
    }

    public function testParserExtractsExceptionDetailsFromLogEntry(): void
    {
        $logPath = storage_path('logs/laravel.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;
        $line = '[2026-06-24 12:00:00] local.ERROR: Login failed {"exception":"RuntimeException","file":"/var/app/Auth.php","line":45,"user_id":12,"request_path":"/login"}' . "\n" . '#0 /var/app/Login.php(12): run()';

        file_put_contents($logPath, $line . "\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->entries('laravel', 1, 'error', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame('2026-06-24 12:00:00', $result['data'][0]['timestamp']);
        $this->assertSame('error', $result['data'][0]['severity']);
        $this->assertSame('Login failed', $result['data'][0]['message']);
        $this->assertSame('RuntimeException', $result['data'][0]['exception_class']);
        $this->assertSame('/var/app/Auth.php', $result['data'][0]['exception_file']);
        $this->assertSame(45, $result['data'][0]['exception_line']);
        $this->assertStringContainsString('#0 /var/app/Login.php', $result['data'][0]['stack_trace']);
    }

    public function testPollingReadsOnlyNewEntriesAfterStoredOffset(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        MonitoredLogFileState::query()->delete();
        file_put_contents($logPath, "[2026-06-24 12:00:00] local.INFO: First poll\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $first = $service->poll('capital_city', '', '', '');

        file_put_contents($logPath, "[2026-06-24 12:01:00] local.INFO: Second poll\n", FILE_APPEND);

        $second = $service->poll('capital_city', '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertCount(1, $first['entries']);
        $this->assertCount(1, $second['entries']);
        $this->assertSame('Second poll', $second['entries'][0]['message']);
    }

    public function testMalformedLogLinesDoNotCrashScanner(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath, "not a laravel log line\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->entries('capital_city', 1, '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame('unknown', $result['data'][0]['severity']);
        $this->assertFalse($result['data'][0]['raw_parseable']);
    }

    public function testLogPollingCreatesDedupedSystemBugReport(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        MonitoredLogFileState::query()->delete();
        MonitoredSystemErrorOccurrence::query()->delete();
        MonitoredSystemErrorReport::query()->delete();
        file_put_contents($logPath, '[2026-06-24 12:00:00] local.ERROR: Same error {"exception":"RuntimeException","file":"/var/app/Auth.php","line":45}' . "\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $service->poll('capital_city', '', '', '');

        file_put_contents($logPath, '[2026-06-24 12:01:00] local.ERROR: Same error {"exception":"RuntimeException","file":"/var/app/Auth.php","line":45}' . "\n", FILE_APPEND);

        $service->poll('capital_city', '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertSame(2, MonitoredSystemErrorOccurrence::count());
        $this->assertSame(2, MonitoredSystemErrorReport::first()->occurrence_count);
    }

    public function testBugChartEndpointReturnsSupportedRangeCounts(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $report = MonitoredSystemErrorReport::factory()->create(['occurrence_count' => 1]);

        MonitoredSystemErrorOccurrence::factory()->create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/bug-chart', [
            'days' => 7,
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertCount(7, $response->json());
        $this->assertSame(1, collect($response->json())->last()['occurrences']);
    }

    public function testBoundedReadReturnsEntriesFromTailOfLargeFile(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        $early = '[2026-01-01 00:00:00] local.INFO: Early entry that should be outside bounded window';
        $recent = '[2026-06-24 12:00:00] local.INFO: Recent entry inside bounded window';

        $padding = str_repeat("[2026-03-01 00:00:00] local.DEBUG: Padding\n", 2000);
        file_put_contents($logPath, $early . "\n" . $padding . $recent . "\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->entries('capital_city', 1, '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $messages = array_column($result['data'], 'message');
        $this->assertContains('Recent entry inside bounded window', $messages);
    }

    public function testSummaryReturnsCorrectCountsFromBoundedRead(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath,
            "[2026-06-24 12:00:00] local.ERROR: Error one\n" .
            "[2026-06-24 12:01:00] local.INFO: Info one\n" .
            "[2026-06-24 12:02:00] local.WARNING: Warning one\n"
        );

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->summary('capital_city', '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame(3, $result['total']);
        $this->assertArrayHasKey('error', $result['by_severity']);
        $this->assertArrayHasKey('info', $result['by_severity']);
        $this->assertArrayHasKey('warning', $result['by_severity']);
    }

    public function testNonErrorLogEntryDoesNotCreateSystemBugReport(): void
    {
        $logPath = storage_path('logs/capital-city-building-upgrades.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        MonitoredLogFileState::query()->delete();
        MonitoredSystemErrorReport::query()->delete();
        file_put_contents($logPath, "[2026-06-24 12:00:00] local.INFO: Just an info message\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $service->poll('capital_city', '', '', '');

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame(0, MonitoredSystemErrorReport::count());
    }

    public function testBugReportsEndpointIncludesFingerprint(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        MonitoredSystemErrorReport::factory()->create([
            'fingerprint' => 'abc123fingerprint',
            'occurrence_count' => 1,
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/bugs');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('abc123fingerprint', $response->json('0.fingerprint'));
    }

    public function testBugReportsEndpointIncludesOccurrenceHistory(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $report = MonitoredSystemErrorReport::factory()->create([
            'occurrence_count' => 2,
        ]);

        MonitoredSystemErrorOccurrence::factory()->create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
            'message' => 'First occurrence message',
        ]);

        MonitoredSystemErrorOccurrence::factory()->create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now()->subMinute(),
            'message' => 'Second occurrence message',
        ]);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/bugs');

        $this->assertSame(200, $response->getStatusCode());
        $occurrences = $response->json('0.occurrences');
        $this->assertCount(2, $occurrences);
    }

    public function testBugChartServiceMethodReturnsOccurrenceCountsPerDay(): void
    {
        $report = MonitoredSystemErrorReport::factory()->create(['occurrence_count' => 3]);

        MonitoredSystemErrorOccurrence::factory()->create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
        ]);

        MonitoredSystemErrorOccurrence::factory()->create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
        ]);

        MonitoredSystemErrorOccurrence::factory()->create([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now()->subDay(),
        ]);

        $service = $this->app->make(AdminLogsDashboardService::class);
        $chart = $service->bugChart(7);

        $todayRow = collect($chart)->firstWhere('period', now()->toDateString());
        $yesterdayRow = collect($chart)->firstWhere('period', now()->subDay()->toDateString());

        $this->assertNotNull($todayRow);
        $this->assertSame(2, $todayRow['occurrences']);
        $this->assertNotNull($yesterdayRow);
        $this->assertSame(1, $yesterdayRow['occurrences']);
    }
}
