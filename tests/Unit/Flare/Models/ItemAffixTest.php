<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItemAffix;

class ItemAffixTest extends TestCase
{
    use RefreshDatabase,
        CreateItemAffix;

    public function testGetPrefixFromSuffix() {
        $affix = $this->createItemAffix([
            'type' => 'suffix'
        ]);

        $this->assertEquals('prefix', $affix->getOppisiteType());
    }

    public function testGetSuffixFromAffix() {
        $affix = $this->createItemAffix([
            'type' => 'affix'
        ]);

        $this->assertEquals('suffix', $affix->getOppisiteType());
    }
}
