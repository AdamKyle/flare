<?php

namespace Tests\Feature\Admin;

use App\Admin\Services\AdminLogsDashboardService;
use App\Admin\Services\MonitoredBugReportService;
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
        $logPath = storage_path('logs/laravel.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        $oldLine = '[2026-01-01 00:00:00] local.INFO: Old message [] []';
        $newLine = '[2026-06-23 12:00:00] local.INFO: New message [] []';
        file_put_contents($logPath, $oldLine . "\n" . $newLine . "\n");

        $service = $this->app->make(AdminLogsDashboardService::class);
        $result = $service->entries('laravel', 1, '', '', '');

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

    public function testLogsDashboardReadFailureCreatesBugReport(): void
    {
        if (posix_getuid() === 0) {
            $this->markTestSkipped('Cannot test file permission failure as root.');
        }

        $logPath = storage_path('logs/laravel.log');
        $existed = file_exists($logPath);
        $original = $existed ? file_get_contents($logPath) : null;

        file_put_contents($logPath, "log content\n");
        chmod($logPath, 0000);

        $service = $this->app->make(AdminLogsDashboardService::class);
        $service->entries('laravel', 1, '', '', '');

        chmod($logPath, 0644);

        if ($original !== null) {
            file_put_contents($logPath, $original);
        } else {
            unlink($logPath);
        }

        $this->assertSame(1, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }
}
