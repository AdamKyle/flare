<?php

namespace Tests\Traits;

use App\Flare\Models\SecurityQuestion;

trait CreateSecurityQuestion {

    public function createSecurityQuestion(array $options = []): SecurityQuestion {
        return SecurityQuestion::factory()->create($options);
    }
}
