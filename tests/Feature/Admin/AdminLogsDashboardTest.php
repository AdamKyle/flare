<?php

namespace Tests\Feature\Admin;

use App\Admin\Services\AdminLogsDashboardService;
use App\Admin\Services\LogReader;
use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\MonitoredLogFileState;
use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use RuntimeException;
use Tests\TestCase;
use Tests\Traits\CreateMonitoredSystemError;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class AdminLogsDashboardTest extends TestCase
{
    use CreateMonitoredSystemError, CreateRole, CreateUser, MockeryPHPUnitIntegration, RefreshDatabase;

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

    public function test_log_entries_api_returns_bounded_summary_for_missing_file(): void
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
        $this->assertArrayHasKey('total', $response->json('summary'));
        $this->assertArrayHasKey('by_severity', $response->json('summary'));
        $this->assertArrayHasKey('chart', $response->json('summary'));
    }

    public function test_removed_separate_log_summary_endpoint_is_unavailable(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/summary', [
            'file' => 'laravel',
        ]);

        $this->assertSame(404, $response->getStatusCode());
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

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, '', '', '');

        $this->assertCount(2, $result['data']);
        $this->assertSame('New message', $result['data'][0]['message']);
        $this->assertSame('Old message', $result['data'][1]['message']);
    }

    public function test_missing_log_file_does_not_create_bug_report(): void
    {
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->entries('laravel', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_empty_log_file_does_not_create_bug_report(): void
    {
        file_put_contents($this->tempLogDir.'/laravel.log', '');

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->entries('laravel', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_unknown_file_key_does_not_create_bug_report(): void
    {
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->entries('unknown_key_not_whitelisted', 1, '', '', '');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function test_entries_endpoint_returns_exact_server_failure_without_replacing_it_with_generic_text(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $service = Mockery::mock(AdminLogsDashboardService::class);
        $service->shouldReceive('entries')->once()->andThrow(
            new RuntimeException('Log storage rejected the bounded read.'),
        );
        $this->instance(AdminLogsDashboardService::class, $service);

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'capital_city',
        ]);

        $this->assertSame(500, $response->getStatusCode());
        $this->assertSame('Log storage rejected the bounded read.', $response->json('message'));
    }

    public function test_unreadable_log_file_is_treated_as_missing_by_safe_discovery(): void
    {
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Cannot test file permission failure as root.');
        }

        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, "[2026-06-24 12:00:00] local.INFO: Unreadable file content\n");
        chmod($filePath, 0000);

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, '', '', '');

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

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, 'error', '', '');

        $this->assertSame('2026-06-24 12:00:00', $result['data'][0]['timestamp']);
        $this->assertSame('error', $result['data'][0]['severity']);
        $this->assertSame('Login failed', $result['data'][0]['message']);
        $this->assertSame('RuntimeException', $result['data'][0]['exception_class']);
        $this->assertSame('/var/app/Auth.php', $result['data'][0]['exception_file']);
        $this->assertSame(45, $result['data'][0]['exception_line']);
        $this->assertArrayNotHasKey('stack_trace', $result['data'][0]);

        $detail = $service->entryDetail('capital_city', $result['data'][0]['detail_id']);

        $this->assertStringContainsString('#0 /var/app/Login.php', $detail['stack_trace']);
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

        $this->assertCount(0, $first['entries']);
        $this->assertCount(1, $second['entries']);
        $this->assertSame('Second poll', $second['entries'][0]['message']);
    }

    public function test_poll_initializes_rotated_active_file_at_end_without_replaying_history(): void
    {
        $oldPath = $this->tempLogDir.'/capital-city-building-upgrades-2026-07-27.log';
        $activePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($oldPath, "[2026-07-27 12:00:00] local.INFO: Rotated history\n");
        file_put_contents($activePath, "[2026-07-28 12:00:00] local.INFO: Current history\n");
        touch($oldPath, now()->subDay()->timestamp);
        touch($activePath, now()->timestamp);

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->poll('capital_city', '', '', '');

        $this->assertSame([], $result['entries']);
        $this->assertSame(
            filesize($activePath),
            MonitoredLogFileState::where('file_path', $activePath)->firstOrFail()->position,
        );
    }

    public function test_poll_reads_replacement_content_after_active_file_is_truncated(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, "[2026-07-28 12:00:00] local.INFO: Original content that is longer\n");
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->poll('capital_city', '', '', '');
        file_put_contents($filePath, "[2026-07-28 12:01:00] local.INFO: New\n");

        $result = $service->poll('capital_city', '', '', '');

        $this->assertCount(1, $result['entries']);
        $this->assertSame('New', $result['entries'][0]['message']);
    }

    public function test_poll_caps_very_large_growth_at_global_byte_budget(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, '');
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->poll('capital_city', '', '', '');
        file_put_contents(
            $filePath,
            str_repeat('x', 3 * 1024 * 1024)."\n[2026-07-28 12:01:00] local.INFO: Beyond first bounded poll\n",
        );

        $service->poll('capital_city', '', '', '');

        $state = MonitoredLogFileState::where('file_path', $filePath)->firstOrFail();
        $this->assertLessThanOrEqual(2 * 1024 * 1024, $state->position);
        $this->assertLessThan(filesize($filePath), $state->position);
    }

    public function test_truncation_reads_only_bounded_tail_of_large_replacement_file(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, str_repeat('o', 4 * 1024 * 1024));
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->poll('capital_city', '', '', '');
        file_put_contents(
            $filePath,
            str_repeat('n', 3 * 1024 * 1024)
            ."\n[2026-07-28 12:01:00] local.INFO: Bounded replacement tail\n",
        );

        $result = $service->poll('capital_city', '', '', '');

        $this->assertSame('Bounded replacement tail', $result['entries'][0]['message']);
        $state = MonitoredLogFileState::where('file_path', $filePath)->firstOrFail();
        $this->assertGreaterThanOrEqual(filesize($filePath) - (2 * 1024 * 1024), $state->position);
    }

    public function test_poll_retains_incomplete_multiline_entry_until_terminating_newline_arrives(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, '');
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->poll('capital_city', '', '', '');
        file_put_contents($filePath, '[2026-07-28 12:01:00] local.ERROR: Partial failure');

        $incomplete = $service->poll('capital_city', '', '', '');
        file_put_contents($filePath, "\n#0 /app/Example.php(1): run()\n", FILE_APPEND);
        $complete = $service->poll('capital_city', '', '', '');

        $this->assertSame([], $incomplete['entries']);
        $this->assertCount(1, $complete['entries']);
        $this->assertSame('Partial failure', $complete['entries'][0]['message']);
    }

    public function test_malformed_log_lines_do_not_crash_scanner(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath, "not a laravel log line\n");

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, '', '', '');

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
        $this->assertSame(1, MonitoredSystemErrorOccurrence::count());
        $this->assertSame(1, MonitoredSystemErrorReport::first()->occurrence_count);
    }

    public function test_bug_chart_endpoint_returns_supported_range_counts(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $report = $this->createMonitoredSystemErrorReport(['occurrence_count' => 1]);

        $this->createMonitoredSystemErrorOccurrence([
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

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, '', '', '');

        $messages = array_column($result['data'], 'message');
        $this->assertContains('Recent entry inside bounded window', $messages);
    }

    public function test_multiple_large_files_share_one_global_request_byte_budget(): void
    {
        $olderPath = $this->tempLogDir.'/capital-city-building-upgrades-2026-07-27.log';
        $newerPath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents(
            $olderPath,
            "[2026-07-27 00:00:00] local.INFO: Older file entry\n"
            .str_repeat("[2026-07-27 00:01:00] local.DEBUG: Older padding\n", 50000),
        );
        file_put_contents(
            $newerPath,
            str_repeat("[2026-07-28 00:01:00] local.DEBUG: Newer padding\n", 50000)
            ."[2026-07-28 12:00:00] local.INFO: Newest file entry\n",
        );
        touch($olderPath, now()->subDay()->timestamp);
        touch($newerPath, now()->timestamp);

        gc_collect_cycles();
        memory_reset_peak_usage();
        $baselineMemory = memory_get_usage(true);

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, '', '', '');
        $requestPeakGrowth = memory_get_peak_usage(true) - $baselineMemory;

        $this->assertLessThanOrEqual(50, count($result['data']));
        $this->assertContains('Newest file entry', array_column($result['data'], 'message'));
        $this->assertNotNull($result['next_cursor']);
        $this->assertLessThan(32 * 1024 * 1024, $requestPeakGrowth);
    }

    public function test_opaque_cursor_pages_backward_without_returning_unbounded_raw_details(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        $padding = str_repeat("[2026-07-27 12:00:00] local.DEBUG: Cursor padding entry\n", 50000);
        file_put_contents(
            $filePath,
            "[2026-07-26 12:00:00] local.INFO: Oldest entry\n"
            .$padding
            ."[2026-07-28 12:00:00] local.ERROR: Newest entry\n#0 /app/Example.php(1): run()\n",
        );

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $first = $service->entries('capital_city', 1, '', '', '');
        $second = $service->entries('capital_city', 1, '', '', '', $first['next_cursor']);

        $this->assertNotNull($first['next_cursor']);
        $this->assertNotSame($first['next_cursor'], $second['next_cursor']);
        $this->assertLessThanOrEqual(50, count($first['data']));
        $this->assertArrayNotHasKey('raw_log_entry', $first['data'][0]);
        $this->assertArrayNotHasKey('stack_trace', $first['data'][0]);
        $this->assertNotEmpty($second['data']);
        $this->assertNotNull(
            $service->entryDetail('capital_city', $second['data'][0]['detail_id']),
        );
    }

    public function test_cursor_returns_every_entry_from_an_overflowing_physical_read_chunk_exactly_once(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        $content = '';

        for ($entryNumber = 1; $entryNumber <= 75; $entryNumber++) {
            $content .= sprintf(
                "[2026-07-28 12:00:00] local.INFO: Cursor entry %d\n",
                $entryNumber,
            );
        }

        file_put_contents($filePath, $content);
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);

        $firstPage = $service->entries('capital_city', 1, '', '', '');
        $secondPage = $service->entries('capital_city', 1, '', '', '', $firstPage['next_cursor']);
        $messages = array_merge(
            array_column($firstPage['data'], 'message'),
            array_column($secondPage['data'], 'message'),
        );

        $this->assertCount(50, $firstPage['data']);
        $this->assertCount(25, $secondPage['data']);
        $this->assertCount(75, $messages);
        $this->assertCount(75, array_unique($messages));
        $this->assertContains('Cursor entry 38', $messages);
        $this->assertSame('Cursor entry 75', $messages[0]);
        $this->assertSame('Cursor entry 26', $messages[49]);
        $this->assertSame('Cursor entry 25', $messages[50]);
        $this->assertSame('Cursor entry 1', $messages[74]);
    }

    public function test_identical_timestamps_across_files_use_deterministic_file_and_byte_ordering(): void
    {
        $olderPath = $this->tempLogDir.'/capital-city-building-upgrades-2026-07-27.log';
        $newerPath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        $olderContent = '';
        $newerContent = '';

        for ($entryNumber = 1; $entryNumber <= 35; $entryNumber++) {
            $olderContent .= sprintf(
                "[2026-07-28 12:00:00] local.INFO: Older file entry %d\n",
                $entryNumber,
            );
        }

        for ($entryNumber = 1; $entryNumber <= 35; $entryNumber++) {
            $newerContent .= sprintf(
                "[2026-07-28 12:00:00] local.INFO: Newer file entry %d\n",
                $entryNumber,
            );
        }

        file_put_contents($olderPath, $olderContent);
        file_put_contents($newerPath, $newerContent);
        touch($olderPath, now()->subDay()->timestamp);
        touch($newerPath, now()->timestamp);
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);

        $firstPage = $service->entries('capital_city', 1, '', '', '');
        $secondPage = $service->entries('capital_city', 1, '', '', '', $firstPage['next_cursor']);
        $messages = array_merge(
            array_column($firstPage['data'], 'message'),
            array_column($secondPage['data'], 'message'),
        );
        $expectedMessages = [];

        for ($entryNumber = 35; $entryNumber >= 1; $entryNumber--) {
            $expectedMessages[] = 'Newer file entry '.$entryNumber;
        }

        for ($entryNumber = 35; $entryNumber >= 1; $entryNumber--) {
            $expectedMessages[] = 'Older file entry '.$entryNumber;
        }

        $this->assertCount(50, $firstPage['data']);
        $this->assertCount(20, $secondPage['data']);
        $this->assertCount(70, array_unique($messages));
        $this->assertSame($expectedMessages, $messages);
    }

    public function test_cursor_page_boundary_preserves_the_complete_multiline_entry(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        $content = "[2026-07-28 12:00:00] local.ERROR: Boundary multiline entry\n"
            ."#0 /app/Boundary.php(10): run()\n";

        for ($entryNumber = 1; $entryNumber <= 50; $entryNumber++) {
            $content .= sprintf(
                "[2026-07-28 12:00:00] local.INFO: Newer entry %d\n",
                $entryNumber,
            );
        }

        file_put_contents($filePath, $content);
        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);

        $firstPage = $service->entries('capital_city', 1, '', '', '');
        $secondPage = $service->entries('capital_city', 1, '', '', '', $firstPage['next_cursor']);
        $detail = $service->entryDetail('capital_city', $secondPage['data'][0]['detail_id']);
        $messages = array_merge(
            array_column($firstPage['data'], 'message'),
            array_column($secondPage['data'], 'message'),
        );

        $this->assertCount(50, $firstPage['data']);
        $this->assertCount(1, $secondPage['data']);
        $this->assertSame('Boundary multiline entry', $secondPage['data'][0]['message']);
        $this->assertCount(1, array_filter(
            $messages,
            fn (string $message): bool => $message === 'Boundary multiline entry',
        ));
        $this->assertNotContains('#0 /app/Boundary.php(10): run()', $messages);
        $this->assertStringContainsString('#0 /app/Boundary.php(10): run()', $detail['stack_trace']);
    }

    public function test_entries_returns_correct_summary_counts_from_the_same_bounded_read(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        file_put_contents($filePath,
            "[2026-06-24 12:00:00] local.ERROR: Error one\n".
            "[2026-06-24 12:01:00] local.INFO: Info one\n".
            "[2026-06-24 12:02:00] local.WARNING: Warning one\n"
        );

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $result = $service->entries('capital_city', 1, '', '', '');

        $this->assertSame(3, $result['summary']['total']);
        $this->assertSame(1, $result['summary']['by_severity']['error']);
        $this->assertSame(1, $result['summary']['by_severity']['info']);
        $this->assertSame(1, $result['summary']['by_severity']['warning']);
    }

    public function test_non_error_log_entry_does_not_create_system_bug_report(): void
    {
        $filePath = $this->tempLogDir.'/capital-city-building-upgrades.log';
        MonitoredLogFileState::query()->delete();
        MonitoredSystemErrorReport::query()->delete();
        file_put_contents($filePath, "[2026-06-24 12:00:00] local.INFO: Just an info message\n");

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $service->poll('capital_city', '', '', '');

        $this->assertSame(0, MonitoredSystemErrorReport::count());
    }

    public function test_bug_reports_endpoint_includes_fingerprint(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());

        $this->createMonitoredSystemErrorReport([
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

        $report = $this->createMonitoredSystemErrorReport([
            'occurrence_count' => 2,
        ]);

        $this->createMonitoredSystemErrorOccurrence([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
            'message' => 'First occurrence message',
        ]);

        $this->createMonitoredSystemErrorOccurrence([
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
        $report = $this->createMonitoredSystemErrorReport(['occurrence_count' => 3]);

        $this->createMonitoredSystemErrorOccurrence([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
        ]);

        $this->createMonitoredSystemErrorOccurrence([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now(),
        ]);

        $this->createMonitoredSystemErrorOccurrence([
            'monitored_system_error_report_id' => $report->id,
            'occurred_at' => now()->subDay(),
        ]);

        $service = $this->app->make(AdminLogsDashboardService::class)->withLogRoot($this->tempLogDir);
        $chart = $service->bugChart(7);

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

    public function test_log_files_api_includes_batch_crafting_channel(): void
    {
        $reader = Mockery::mock(LogReader::class);
        $reader->shouldReceive('discoverFiles')->andReturn([]);
        $service = new AdminLogsDashboardService(resolve(MonitoredBugReportService::class), $reader);

        $files = $service->listFiles();

        $this->assertContains('batch_crafting', array_column($files, 'key'));
    }

    public function test_batch_crafting_log_entries_endpoint_returns_mocked_lines(): void
    {
        $admin = $this->createAdmin($this->createAdminRole());
        $reader = Mockery::mock(LogReader::class);
        $reader->shouldReceive('discoverFiles')->andReturn(['/mock/batch-crafting.log']);
        $reader->shouldReceive('fileSize')->andReturn(100);
        $reader->shouldReceive('readBackward')->andReturn([
            'content' => '[2026-06-24 12:00:00] local.ERROR: Batch craft exploded {"exception":"RuntimeException","batch_crafting_id":10}'."\n",
            'start' => 0,
            'end' => 100,
            'file_size' => 100,
        ]);
        $this->app->instance(AdminLogsDashboardService::class, new AdminLogsDashboardService(resolve(MonitoredBugReportService::class), $reader));

        $response = $this->actingAs($admin)->call('GET', '/api/admin/monitoring/logs/entries', [
            'file' => 'batch_crafting',
            'severity' => 'error',
            'date_from' => '2026-06-24',
            'date_to' => '2026-06-24',
        ]);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('Batch craft exploded', $response->json('data.0.message'));
    }

    public function test_batch_crafting_error_log_creates_system_bug_report_from_mocked_reader(): void
    {
        MonitoredLogFileState::query()->delete();
        MonitoredLogFileState::create([
            'channel_key' => 'batch_crafting',
            'file_path' => '/mock/batch-crafting.log',
            'position' => 0,
            'file_size' => 0,
            'last_scanned_at' => now(),
        ]);
        $reader = Mockery::mock(LogReader::class);
        $reader->shouldReceive('discoverFiles')->andReturn(['/mock/batch-crafting.log']);
        $reader->shouldReceive('fileSize')->andReturn(100);
        $reader->shouldReceive('readBackward')->andReturn([
            'content' => '[2026-06-24 12:00:00] batch_crafting.ERROR: Batch craft exploded {"exception":"RuntimeException","batch_crafting_id":10}'."\n",
            'start' => 0, 'end' => 100, 'file_size' => 100,
        ]);
        $service = new AdminLogsDashboardService(resolve(MonitoredBugReportService::class), $reader);

        $service->poll('batch_crafting', '', '', '');

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertStringContainsString('Batch craft exploded', MonitoredSystemErrorReport::first()->latest_message);
    }

    public function test_duplicate_batch_crafting_error_log_dedupes_system_bug_report_from_mocked_reader(): void
    {
        MonitoredLogFileState::query()->delete();
        MonitoredLogFileState::create([
            'channel_key' => 'batch_crafting',
            'file_path' => '/mock/batch-crafting.log',
            'position' => 0,
            'file_size' => 0,
            'last_scanned_at' => now(),
        ]);
        $reader = Mockery::mock(LogReader::class);
        $reader->shouldReceive('discoverFiles')->andReturn(['/mock/batch-crafting.log', '/mock/batch-crafting-2026-06-24.log']);
        $reader->shouldReceive('fileSize')->andReturn(100);
        $reader->shouldReceive('readBackward')->andReturn([
            'content' => '[2026-06-24 12:00:00] batch_crafting.ERROR: Same batch craft error {"exception":"RuntimeException","file":"/app/Batch.php","line":12}'."\n",
            'start' => 0, 'end' => 100, 'file_size' => 100,
        ]);
        $service = new AdminLogsDashboardService(resolve(MonitoredBugReportService::class), $reader);

        $service->poll('batch_crafting', '', '', '');

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertSame(1, MonitoredSystemErrorOccurrence::count());
    }

    public function test_generic_monitored_error_log_creates_system_bug_report_from_mocked_reader(): void
    {
        MonitoredLogFileState::query()->delete();
        MonitoredLogFileState::create([
            'channel_key' => 'laravel',
            'file_path' => '/mock/laravel.log',
            'position' => 0,
            'file_size' => 0,
            'last_scanned_at' => now(),
        ]);
        $reader = Mockery::mock(LogReader::class);
        $reader->shouldReceive('discoverFiles')->andReturn(['/mock/laravel.log']);
        $reader->shouldReceive('fileSize')->andReturn(100);
        $reader->shouldReceive('readBackward')->andReturn([
            'content' => '[2026-06-24 12:00:00] local.ERROR: Generic monitored error {"exception":"RuntimeException","file":"/app/Auth.php","line":45}'."\n",
            'start' => 0, 'end' => 100, 'file_size' => 100,
        ]);
        $service = new AdminLogsDashboardService(resolve(MonitoredBugReportService::class), $reader);

        $service->poll('laravel', '', '', '');

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertStringContainsString('Generic monitored error', MonitoredSystemErrorReport::first()->latest_message);
    }

    public function test_generic_monitored_error_log_dedupe_still_works_from_mocked_reader(): void
    {
        MonitoredLogFileState::query()->delete();
        MonitoredLogFileState::create([
            'channel_key' => 'laravel',
            'file_path' => '/mock/laravel.log',
            'position' => 0,
            'file_size' => 0,
            'last_scanned_at' => now(),
        ]);
        $reader = Mockery::mock(LogReader::class);
        $reader->shouldReceive('discoverFiles')->andReturn(['/mock/laravel.log', '/mock/laravel-2026-06-24.log']);
        $reader->shouldReceive('fileSize')->andReturn(100);
        $reader->shouldReceive('readBackward')->andReturn([
            'content' => '[2026-06-24 12:00:00] local.ERROR: Same generic monitored error {"exception":"RuntimeException","file":"/app/Auth.php","line":45}'."\n",
            'start' => 0, 'end' => 100, 'file_size' => 100,
        ]);
        $service = new AdminLogsDashboardService(resolve(MonitoredBugReportService::class), $reader);

        $service->poll('laravel', '', '', '');

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertSame(1, MonitoredSystemErrorOccurrence::count());
    }
}
