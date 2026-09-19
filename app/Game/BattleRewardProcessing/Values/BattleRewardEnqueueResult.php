<?php

namespace App\Game\BattleRewardProcessing\Values;

use App\Flare\Models\CharacterBattleRewardRequest;
use Throwable;

class BattleRewardEnqueueResult
{
    /**
     * @param ?CharacterBattleRewardRequest $request
     * @param ?Throwable $failure
     */
    public function __construct(
        private readonly ?CharacterBattleRewardRequest $request,
        private readonly ?Throwable $failure = null,
    ) {}

    /**
     * Build a successful enqueue result.
     *
     * @param CharacterBattleRewardRequest $request
     * @return BattleRewardEnqueueResult
     */
    public static function success(CharacterBattleRewardRequest $request): BattleRewardEnqueueResult
    {
        return new BattleRewardEnqueueResult($request);
    }

    /**
     * Build a failed enqueue result.
     *
     * @param Throwable $failure
     * @return BattleRewardEnqueueResult
     */
    public static function failed(Throwable $failure): BattleRewardEnqueueResult
    {
        return new BattleRewardEnqueueResult(null, $failure);
    }

    /**
     * Return the created reward request when enqueue succeeded.
     *
     * @return ?CharacterBattleRewardRequest
     */
    public function request(): ?CharacterBattleRewardRequest
    {
        return $this->request;
    }

    /**
     * Return the enqueue failure when enqueue did not succeed.
     *
     * @return ?Throwable
     */
    public function failure(): ?Throwable
    {
        return $this->failure;
    }

    /**
     * Determine whether enqueue succeeded.
     *
     * @return bool
     */
    public function successful(): bool
    {
        return ! is_null($this->request);
    }
}
