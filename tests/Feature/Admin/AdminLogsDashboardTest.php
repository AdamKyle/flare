<?php

namespace Tests\Feature\Admin;

use App\Admin\Services\AdminLogsDashboardService;
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

    private string $tempLogDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempLogDir = sys_get_temp_dir().'/flare-admin-logs-test-'.uniqid();
        mkdir($this->tempLogDir, 0755, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempLogDir.'/*') ?: [] as $file) {
            if (is_file($file)) {
                chmod($file, 0644);
                unlink($file);
            }
        }
        if (is_dir($this->tempLogDir)) {
            rmdir($this->tempLogDir);
        }
        parent::tearDown();
    }

    public function test_non_admin_cannot_access_logs_dashboard_page(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/admin/monitoring/logs');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_admin_can_view_logs_dashboard_page(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/admin/monitoring/logs');

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_non_admin_cannot_access_logs_files_api(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/logs/files');

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_log_files_api_returns_whitelisted_files(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->app->instance(
            AdminLogsDashboardService::class,
            $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir),
        );

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        $this->assertSame(200, $response->getStatusCode());
        $files = $response->json();
        $this->assertIsArray($files);

        $keys = array_column($files, 'key');
        $this->assertContains('laravel', $keys);
        $this->assertContains('faction_loyalty', $keys);
        $this->assertContains('exploration_automation', $keys);
        $this->assertContains('reward_processing', $keys);
    }

    public function test_log_entries_api_returns_empty_for_missing_log_file(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->app->instance(
            AdminLogsDashboardService::class,
            $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir),
        );

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'laravel',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json('data'));
    }

    public function test_non_admin_cannot_access_log_entries_api(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'laravel',
        ]);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_log_entries_api_returns_empty_for_unknown_file_key(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'unknown_key_not_whitelisted',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame([], $response->json('data'));
    }

    public function test_log_summary_api_returns_expected_keys_for_missing_file(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->app->instance(
            AdminLogsDashboardService::class,
            $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir),
        );

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/summary', [
            'file' => 'laravel',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertArrayHasKey('total', $response->json());
        $this->assertArrayHasKey('by_severity', $response->json());
        $this->assertArrayHasKey('chart', $response->json());
    }

    public function test_non_admin_cannot_access_log_summary_api(): void
    {
        $user = $this->createUser();

        $response = $this->actingAs($user)->call('GET', '/api/admin/monitoring/logs/summary', [
            'file' => 'laravel',
        ]);

        $this->assertSame(302, $response->getStatusCode());
    }

    public function test_log_files_api_includes_exists_and_size_fields(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $this->app->instance(
            AdminLogsDashboardService::class,
            $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir),
        );

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        $files = $response->json();
        $this->assertArrayHasKey('exists', $files[0]);
        $this->assertArrayHasKey('size_bytes', $files[0]);
        $this->assertArrayHasKey('label', $files[0]);
    }

    public function test_log_entries_returns_newest_lines_first_when_file_has_content(): void
    {
        $oldLine = '[2026-06-23 12:00:00] local.INFO: Old message';
        $newLine = '[2026-06-24 12:00:00] local.INFO: New message';
        file_put_contents($this->tempLogDir.'/capital-city-building-upgrades.log', $oldLine."\n".$newLine."\n");

        $result = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('capital_city', 1, '', '', '');

        $this->assertCount(2, $result['data']);
        $this->assertSame('New message', $result['data'][0]['message']);
        $this->assertSame('Old message', $result['data'][1]['message']);
    }

    public function test_missing_log_file_does_not_create_bug_report(): void
    {
        $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('laravel', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_empty_log_file_does_not_create_bug_report(): void
    {
        file_put_contents($this->tempLogDir.'/laravel.log', '');

        $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('laravel', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_unknown_file_key_does_not_create_bug_report(): void
    {
        $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('unknown_key_not_whitelisted', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_unreadable_log_file_is_treated_as_missing_by_safe_discovery(): void
    {
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Cannot test file permission failure as root.');
        }

        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, "[2026-06-24 12:00:00] local.INFO: Unreadable file content\n");
        chmod($filePath, 0000);

        $result = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('capital_city', 1, '', '', '');

        chmod($filePath, 0644);

        $this->assertSame([], $result['data']);
        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_dated_laravel_logs_are_discovered(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        file_put_contents($this->tempLogDir.'/laravel-2026-06-24.log', "[2026-06-24 12:00:00] local.INFO: Dated log message\n");
        $this->app->instance(
            AdminLogsDashboardService::class,
            $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir),
        );

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        $laravel = collect($response->json())->firstWhere('key', 'laravel');

        $this->assertTrue($laravel['exists']);
        $this->assertContains('laravel-2026-06-24.log', $laravel['files']);
    }

    public function test_channel_is_not_missing_when_matching_dated_file_exists(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        file_put_contents($this->tempLogDir.'/exploration-automation-2026-06-24.log', "[2026-06-24 12:00:00] local.INFO: Exploration dated log\n");
        $this->app->instance(
            AdminLogsDashboardService::class,
            $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir),
        );

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/files');

        $exploration = collect($response->json())->firstWhere('key', 'exploration_automation');

        $this->assertTrue($exploration['exists']);
    }

    public function test_parser_extracts_exception_details_from_log_entry(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        $line = '[2026-06-24 12:00:00] local.ERROR: Login failed {"exception":"RuntimeException","file":"/var/app/Auth.php","line":45,"user_id":12,"request_path":"/login"}'."\n".'#0 /var/app/Login.php(12): run()';
        file_put_contents($filePath, $line."\n");

        $result = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('capital_city', 1, 'error', '', '');

        $this->assertSame('2026-06-24 12:00:00', $result['data'][0]['timestamp']);
        $this->assertSame('error', $result['data'][0]['severity']);
        $this->assertSame('Login failed', $result['data'][0]['message']);
        $this->assertSame('RuntimeException', $result['data'][0]['exception_class']);
        $this->assertSame('/var/app/Auth.php', $result['data'][0]['exception_file']);
        $this->assertSame(45, $result['data'][0]['exception_line']);
        $this->assertStringContainsString('#0 /var/app/Login.php', $result['data'][0]['stack_trace']);
    }

    public function test_polling_reads_only_new_entries_after_stored_offset(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        MonitoredLogFileState::query()->delete();
        file_put_contents($filePath, "[2026-06-24 12:00:00] local.INFO: First poll\n");

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $first = $service->poll('capital_city', '', '', '');

        file_put_contents($filePath, "[2026-06-24 12:01:00] local.INFO: Second poll\n", FILE_APPEND);

        $second = $service->poll('capital_city', '', '', '');

        $this->assertCount(1, $first['entries']);
        $this->assertSame('First poll', $first['entries'][0]['message']);
        $this->assertCount(1, $second['entries']);
        $this->assertSame('Second poll', $second['entries'][0]['message']);
    }

    public function test_malformed_log_lines_do_not_crash_scanner(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, "not a laravel log line\n");

        $result = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('capital_city', 1, '', '', '');

        $this->assertCount(1, $result['data']);
        $this->assertSame('unknown', $result['data'][0]['severity']);
        $this->assertFalse($result['data'][0]['raw_parseable']);
    }

    public function test_log_polling_creates_deduped_system_bug_report(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        MonitoredLogFileState::query()->delete();
        MonitoredSystemErrorOccurrence::query()->delete();
        MonitoredSystemErrorReport::query()->delete();
        file_put_contents($filePath, '[2026-06-24 12:00:00] local.ERROR: Same error {"exception":"RuntimeException","file":"/var/app/Auth.php","line":45}'."\n");

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->poll('capital_city', '', '', '');

        file_put_contents($filePath, '[2026-06-24 12:01:00] local.ERROR: Same error {"exception":"RuntimeException","file":"/var/app/Auth.php","line":45}'."\n", FILE_APPEND);

        $service->poll('capital_city', '', '', '');

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertSame(2, MonitoredSystemErrorOccurrence::count());
        $this->assertSame(2, MonitoredSystemErrorReport::first()->occurrence_count);
    }

    public function test_bug_chart_endpoint_returns_supported_range_counts(): void
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

    public function test_bounded_read_returns_entries_from_tail_of_large_file(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        $early = '[2026-01-01 00:00:00] local.INFO: Early entry outside bounded window';
        $recent = '[2026-06-24 12:00:00] local.INFO: Recent entry inside bounded window';
        $padding = str_repeat("[2026-03-01 00:00:00] local.DEBUG: Padding\n", 50000);
        file_put_contents($filePath, $early."\n".$padding.$recent."\n");

        $result = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->entries('capital_city', 1, '', '', '');

        $messages = array_column($result['data'], 'message');
        $this->assertContains('Recent entry inside bounded window', $messages);
    }

    public function test_summary_returns_correct_counts_from_bounded_read(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath,
            "[2026-06-24 12:00:00] local.ERROR: Error one\n".
            "[2026-06-24 12:01:00] local.INFO: Info one\n".
            "[2026-06-24 12:02:00] local.WARNING: Warning one\n"
        );

        $result = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->summary('capital_city', '', '', '');

        $this->assertSame(3, $result['total']);
        $this->assertSame(1, $result['by_severity']['error']);
        $this->assertSame(1, $result['by_severity']['info']);
        $this->assertSame(1, $result['by_severity']['warning']);
    }

    public function test_non_error_log_entry_does_not_create_system_bug_report(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        MonitoredLogFileState::query()->delete();
        MonitoredSystemErrorReport::query()->delete();
        file_put_contents($filePath, "[2026-06-24 12:00:00] local.INFO: Just an info message\n");

        $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->poll('capital_city', '', '', '');

        $this->assertSame(0, MonitoredSystemErrorReport::count());
    }

    public function test_bug_reports_endpoint_includes_fingerprint(): void
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

    public function test_bug_reports_endpoint_includes_occurrence_history(): void
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

    public function test_bug_chart_service_method_returns_occurrence_counts_per_day(): void
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

        $chart = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir)->bugChart(7);

        $todayRow = collect($chart)->firstWhere('period', now()->toDateString());
        $yesterdayRow = collect($chart)->firstWhere('period', now()->subDay()->toDateString());

        $this->assertNotNull($todayRow);
        $this->assertSame(2, $todayRow['occurrences']);
        $this->assertNotNull($yesterdayRow);
        $this->assertSame(1, $yesterdayRow['occurrences']);
    }

    public function test_with_log_root_isolates_discovery_from_real_storage_logs(): void
    {
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);

        $result = $service->entries('laravel', 1, '', '', '');

        $this->assertSame([], $result['data']);
        $this->assertSame(0, $result['total']);
    }
}
