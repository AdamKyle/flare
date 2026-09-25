<?php

namespace App\Game\Gems\Progression\Values;

use Throwable;

class GemWorldRewardApplicationResult
{
    /**
     * @param ?array $result
     * @param ?Throwable $failure
     */
    public function __construct(
        private readonly ?array $result,
        private readonly ?Throwable $failure = null,
    ) {}

    /**
     * Build a successful Gem World reward application result.
     *
     * @param array $result
     * @return GemWorldRewardApplicationResult
     */
    public static function success(array $result): GemWorldRewardApplicationResult
    {
        return new GemWorldRewardApplicationResult($result);
    }

    /**
     * Build a failed Gem World reward application result.
     *
     * @param Throwable $failure
     * @return GemWorldRewardApplicationResult
     */
    public static function failed(Throwable $failure): GemWorldRewardApplicationResult
    {
        return new GemWorldRewardApplicationResult(null, $failure);
    }

    /**
     * Determine whether the Gem World reward operation completed successfully.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return ! is_null($this->result);
    }

    /**
     * Return the successful Gem World reward payload.
     *
     * @return ?array
     */
    public function result(): ?array
    {
        return $this->result;
    }

    /**
     * Return the Gem World reward failure when the operation did not succeed.
     *
     * @return ?Throwable
     */
    public function failure(): ?Throwable
    {
        return $this->failure;
    }
}
