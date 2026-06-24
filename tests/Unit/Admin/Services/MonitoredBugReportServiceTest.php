<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\MonitoredBugReportService;
use App\Flare\Models\MonitoredSystemErrorOccurrence;
use App\Flare\Models\MonitoredSystemErrorReport;
use App\Flare\Models\SuggestionAndBugs;
use App\Game\Core\Values\FeedbackType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoredBugReportServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCreatesNewBugReportWhenNoneExistsForFingerprint(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Something broke', [], 'RuntimeException');

        $this->assertSame(1, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testSameFingerprintDeduplicatesToOneBugReport(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Something broke', [], 'RuntimeException');
        $service->reportError('test-source', 'Something broke', [], 'RuntimeException');

        $this->assertSame(1, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testDifferentMessagesCreateSeparateReports(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Error one', [], 'RuntimeException');
        $service->reportError('test-source', 'Error two', [], 'RuntimeException');

        $this->assertSame(2, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testSameTitleWithDifferentExceptionClassCreatesSeperateReports(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Same message', [], 'RuntimeException');
        $service->reportError('test-source', 'Same message', [], 'InvalidArgumentException');

        $this->assertSame(2, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testSameTitleWithDifferentSourceIdCreatesSeperateReports(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Same message', [], 'RuntimeException', null, 'source-id-1');
        $service->reportError('test-source', 'Same message', [], 'RuntimeException', null, 'source-id-2');

        $this->assertSame(2, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testFingerprintIsStoredInDescription(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Something broke', [], 'RuntimeException');

        $bug = SuggestionAndBugs::where('type', FeedbackType::BUG)->first();

        $this->assertStringContainsString('Fingerprint: ', $bug->description);
    }

    public function testStoresCharacterIdWhenProvided(): void
    {
        $character = (new \Tests\Setup\Character\CharacterFactory)->createBaseCharacter()->getCharacter();
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Error with character', [], 'RuntimeException', $character->id);

        $bug = SuggestionAndBugs::where('type', FeedbackType::BUG)->first();

        $this->assertSame($character->id, $bug->character_id);
    }

    public function testCharacterIdIsNullWhenNotProvided(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('test-source', 'Error no character', [], 'RuntimeException');

        $bug = SuggestionAndBugs::where('type', FeedbackType::BUG)->first();

        $this->assertNull($bug->character_id);
    }

    public function testTitleIsPrefixedWithAutoAndSourceSystem(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError('my-system', 'Something went wrong', [], 'RuntimeException');

        $bug = SuggestionAndBugs::where('type', FeedbackType::BUG)->first();

        $this->assertStringStartsWith('[Auto] my-system:', $bug->title);
    }

    public function testContextSensitiveKeysAreRedacted(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportError(
            'test-source',
            'An error occurred',
            ['password' => 'secret123', 'user_id' => 5],
            'RuntimeException',
        );

        $bug = SuggestionAndBugs::where('type', FeedbackType::BUG)->first();

        $this->assertStringNotContainsString('secret123', $bug->description);
        $this->assertStringContainsString('[REDACTED]', $bug->description);
    }

    public function testBugReportCreationFailureDoesNotRecurse(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        SuggestionAndBugs::creating(function () {
            throw new \RuntimeException('DB write failed');
        });

        $service->reportError('test-source', 'Something broke', [], 'RuntimeException');

        $this->assertSame(0, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
    }

    public function testFirstErrorCreatesSystemBugReportAndOccurrence(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportLogEntry([
            'timestamp' => '2026-06-24 12:00:00',
            'severity' => 'error',
            'channel' => 'local',
            'message' => 'Something broke',
            'exception_class' => 'RuntimeException',
            'exception_file' => '/var/app/Auth.php',
            'exception_line' => 45,
            'stack_trace' => '#0 /var/app/Login.php(12): run()',
            'raw_log_entry' => 'raw error',
            'file_path' => storage_path('logs/laravel.log'),
            'context_payload' => [],
        ]);

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertSame(1, MonitoredSystemErrorOccurrence::count());
        $this->assertSame(1, MonitoredSystemErrorReport::first()->occurrence_count);
    }

    public function testDuplicateErrorUpdatesExistingSystemBugAndCreatesOccurrence(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportLogEntry([
            'timestamp' => '2026-06-24 12:00:00',
            'severity' => 'error',
            'channel' => 'local',
            'message' => 'Same error',
            'exception_class' => 'RuntimeException',
            'exception_file' => '/var/app/Auth.php',
            'exception_line' => 45,
            'stack_trace' => '#0 /var/app/Login.php(12): run()',
            'raw_log_entry' => 'raw error one',
            'file_path' => storage_path('logs/laravel.log'),
            'context_payload' => [],
        ]);
        $service->reportLogEntry([
            'timestamp' => '2026-06-24 12:01:00',
            'severity' => 'error',
            'channel' => 'local',
            'message' => 'Same error',
            'exception_class' => 'RuntimeException',
            'exception_file' => '/var/app/Auth.php',
            'exception_line' => 45,
            'stack_trace' => '#0 /var/app/Login.php(12): run()',
            'raw_log_entry' => 'raw error two',
            'file_path' => storage_path('logs/laravel.log'),
            'context_payload' => [],
        ]);

        $this->assertSame(1, MonitoredSystemErrorReport::count());
        $this->assertSame(2, MonitoredSystemErrorOccurrence::count());
        $this->assertSame(2, MonitoredSystemErrorReport::first()->occurrence_count);
        $this->assertSame('raw error two', MonitoredSystemErrorReport::first()->latest_raw_log_entry);
    }

    public function testDifferentFingerprintCreatesSeparateSystemBugReport(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportLogEntry([
            'timestamp' => '2026-06-24 12:00:00',
            'severity' => 'error',
            'channel' => 'local',
            'message' => 'First error',
            'exception_class' => 'RuntimeException',
            'exception_file' => '/var/app/Auth.php',
            'exception_line' => 45,
            'stack_trace' => '#0 /var/app/Login.php(12): run()',
            'raw_log_entry' => 'raw error',
            'file_path' => storage_path('logs/laravel.log'),
            'context_payload' => [],
        ]);
        $service->reportLogEntry([
            'timestamp' => '2026-06-24 12:00:00',
            'severity' => 'error',
            'channel' => 'local',
            'message' => 'Second error',
            'exception_class' => 'RuntimeException',
            'exception_file' => '/var/app/Auth.php',
            'exception_line' => 45,
            'stack_trace' => '#0 /var/app/Login.php(12): run()',
            'raw_log_entry' => 'raw error',
            'file_path' => storage_path('logs/laravel.log'),
            'context_payload' => [],
        ]);

        $this->assertSame(2, MonitoredSystemErrorReport::count());
    }

    public function testOccurrenceStoresFullCaptureContext(): void
    {
        $service = resolve(MonitoredBugReportService::class);

        $service->reportLogEntry([
            'timestamp' => '2026-06-24 12:00:00',
            'severity' => 'critical',
            'channel' => 'local',
            'message' => 'Full capture',
            'exception_class' => 'RuntimeException',
            'exception_file' => '/var/app/Auth.php',
            'exception_line' => 45,
            'stack_trace' => '#0 /var/app/Login.php(12): run()',
            'raw_log_entry' => 'raw full capture',
            'file_path' => storage_path('logs/laravel.log'),
            'context_payload' => [
                'user_id' => 10,
                'request_path' => '/login',
                'job_class' => 'App\\Jobs\\LoginJob',
                'queue' => 'default',
                'token' => 'secret',
            ],
        ]);

        $occurrence = MonitoredSystemErrorOccurrence::first();

        $this->assertSame('critical', $occurrence->level);
        $this->assertSame('local', $occurrence->channel);
        $this->assertSame('Full capture', $occurrence->message);
        $this->assertSame('RuntimeException', $occurrence->exception_class);
        $this->assertSame('/var/app/Auth.php', $occurrence->exception_file);
        $this->assertSame(45, $occurrence->exception_line);
        $this->assertSame(10, $occurrence->user_id);
        $this->assertSame('/login', $occurrence->request_path);
        $this->assertSame('App\\Jobs\\LoginJob', $occurrence->job_class);
        $this->assertSame('default', $occurrence->queue);
        $this->assertSame('[REDACTED]', $occurrence->context['token']);
    }

    public function testUserSubmittedBugFactoryStillCreatesFeedbackBug(): void
    {
        SuggestionAndBugs::factory()->create(['type' => FeedbackType::BUG]);

        $this->assertSame(1, SuggestionAndBugs::where('type', FeedbackType::BUG)->count());
        $this->assertSame(0, MonitoredSystemErrorReport::count());
    }
}
