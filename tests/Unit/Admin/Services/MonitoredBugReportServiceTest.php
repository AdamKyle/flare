<?php

namespace Tests\Unit\Admin\Services;

use App\Admin\Services\MonitoredBugReportService;
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
}
