<?php

namespace Tests\Unit\Flare\Transformers\Serializers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Transformers\Serializers\CoreSerializer;
use Tests\TestCase;

class CoreSerializerTest extends TestCase
{
    use RefreshDatabase;

    public function testCollection() {
        $coreSerializer = new CoreSerializer;

        $array = $coreSerializer->collection('data', ['id' => 1]);

        $this->assertEquals($array, [
            'data' => [
                'id' => 1
            ]
        ]);
    }

    public function testItem() {
        $coreSerializer = new CoreSerializer;

        $array = $coreSerializer->item('data', ['id' => 1]);

        $this->assertEquals($array, [
            'data' => [
                'id' => 1
            ]
        ]);
    }
}
