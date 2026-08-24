<?php

namespace Tests\Unit\Flare\Transformers\Serializer;

use App\Flare\Transformers\Serializer\PlainDataSerializer;
use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\ResourceInterface;
use Mockery;
use Tests\TestCase;

class PlainDataSerializerTest extends TestCase
{
    public function test_collection_returns_the_data_unmodified(): void
    {
        $data = [['id' => 1], ['id' => 2]];

        $this->assertSame($data, (new PlainDataSerializer())->collection('items', $data));
    }

    public function test_item_returns_the_data_unmodified(): void
    {
        $data = ['id' => 1];

        $this->assertSame($data, (new PlainDataSerializer())->item('item', $data));
    }

    public function test_null_returns_null(): void
    {
        $this->assertNull((new PlainDataSerializer())->null());
    }

    public function test_included_data_returns_an_empty_array(): void
    {
        $resource = Mockery::mock(ResourceInterface::class);

        $this->assertSame([], (new PlainDataSerializer())->includedData($resource, ['id' => 1]));
    }

    public function test_meta_returns_an_empty_array(): void
    {
        $this->assertSame([], (new PlainDataSerializer())->meta(['some' => 'meta']));
    }

    public function test_paginator_returns_an_empty_array(): void
    {
        $paginator = Mockery::mock(PaginatorInterface::class);

        $this->assertSame([], (new PlainDataSerializer())->paginator($paginator));
    }

    public function test_cursor_returns_an_empty_array(): void
    {
        $cursor = Mockery::mock(CursorInterface::class);

        $this->assertSame([], (new PlainDataSerializer())->cursor($cursor));
    }
}
