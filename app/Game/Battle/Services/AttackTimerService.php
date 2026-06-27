<?php

namespace App\Game\Battle\Services;

use App\Flare\Models\Character;
use App\Game\Automation\Services\AutomationRestrictionService;

class AttackTimerService
{
    public function __construct(
        private readonly AutomationRestrictionService $automationRestrictionService,
    ) {}

    public function normalizeExpiredAttackTimer(Character $character): Character
    {
        $character = $character->refresh();

        if ($character->can_attack || is_null($character->can_attack_again_at)) {
            return $character;
        }

        if ($character->can_attack_again_at->greaterThan(now())) {
            return $character;
        }

        if ($character->is_dead) {
            return $character;
        }

        if ($this->automationRestrictionService->isBlocked($character, AutomationRestrictionService::MANUAL_FIGHTING)) {
            return $character;
        }

        $character->update([
            'can_attack' => true,
            'can_attack_again_at' => null,
        ]);

        return $character->refresh();
    }
}
