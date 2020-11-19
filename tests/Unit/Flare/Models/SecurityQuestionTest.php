<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateSecurityQuestion;
use Tests\Traits\CreateUser;

class SecurityQuestionTest extends TestCase
{
    use RefreshDatabase, 
        CreateUser, 
        CreateSecurityQuestion;

    public function testModelRelationships() {
        $securityQuestions = $this->createSecurityQuestion([
            'user_id' => $this->createUser()->id,
            'question' => 'sample',
            'answer' => 'sample',
        ]);

        $this->assertNotNull($securityQuestions->user);
    }
}
