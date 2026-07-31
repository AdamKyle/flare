<?php

namespace App\Game\Events\Values;

use Exception;

class ScheduledEventStatus
{
    const SCHEDULED = 'scheduled';

    const QUEUED = 'queued';

    const STARTING = 'starting';

    const RUNNING = 'running';

    const CANCELLING = 'cancelling';

    const CANCELLED = 'cancelled';

    const COMPLETED = 'completed';

    const FAILED = 'failed';

    private string $value;

    /**
     * @var string[]
     */
    protected static array $values = [
        self::SCHEDULED => self::SCHEDULED,
        self::QUEUED => self::QUEUED,
        self::STARTING => self::STARTING,
        self::RUNNING => self::RUNNING,
        self::CANCELLING => self::CANCELLING,
        self::CANCELLED => self::CANCELLED,
        self::COMPLETED => self::COMPLETED,
        self::FAILED => self::FAILED,
    ];

    /**
     * Throws if the value does not exist in the array of const values.
     *
     * @throws Exception
     */
    public function __construct(string $value)
    {
        if (! in_array($value, self::$values)) {
            throw new Exception($value.' does not exist.');
        }

        $this->value = $value;
    }

    /**
     * Statuses that never reserve a raid/dispatch slot again once reached.
     *
     * @return string[]
     */
    public static function terminalStatuses(): array
    {
        return [self::CANCELLED, self::COMPLETED, self::FAILED];
    }

    /**
     * Statuses which currently block a fresh dispatch from being accepted.
     *
     * @return string[]
     */
    public static function nonDispatchableStatuses(): array
    {
        return [self::QUEUED, self::STARTING, self::RUNNING, self::CANCELLING, ...self::terminalStatuses()];
    }

    /**
     * Statuses where the schedule/raid is considered active and reserving its
     * map(s): queued/scheduled waiting for dispatch, starting, running, or
     * cancelling. Scheduled is included so a freshly created schedule (or a
     * seasonal child raid waiting on its parent to start it) is immediately
     * treated as active everywhere active status is checked.
     *
     * @return string[]
     */
    public static function activeStatuses(): array
    {
        return [self::SCHEDULED, self::QUEUED, self::STARTING, self::RUNNING, self::CANCELLING];
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isScheduled(): bool
    {
        return $this->value === self::SCHEDULED;
    }

    public function isQueued(): bool
    {
        return $this->value === self::QUEUED;
    }

    public function isStarting(): bool
    {
        return $this->value === self::STARTING;
    }

    public function isRunning(): bool
    {
        return $this->value === self::RUNNING;
    }

    public function isCancelling(): bool
    {
        return $this->value === self::CANCELLING;
    }

    public function isCancelled(): bool
    {
        return $this->value === self::CANCELLED;
    }

    public function isCompleted(): bool
    {
        return $this->value === self::COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->value === self::FAILED;
    }

    /**
     * Matches the state/boolean contract: starting, running, and cancelling all
     * keep the legacy `currently_running` boolean true.
     */
    public function currentlyRunningBoolean(): bool
    {
        return in_array($this->value, [self::STARTING, self::RUNNING, self::CANCELLING], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this->value, self::terminalStatuses(), true);
    }

    public function isDispatchable(): bool
    {
        return ! in_array($this->value, self::nonDispatchableStatuses(), true);
    }
}
