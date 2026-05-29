<?php

namespace App\Flare\Transformers\Serializer;

use League\Fractal\Pagination\CursorInterface;
use League\Fractal\Pagination\PaginatorInterface;
use League\Fractal\Resource\ResourceInterface;
use League\Fractal\Serializer\SerializerAbstract;

class PlainDataSerializer extends SerializerAbstract
{
    public function collection(?string $resourceKey, array $data): array
    {
        return $data;
    }

    public function item(?string $resourceKey, array $data): array
    {
        return $data;
    }

    public function null(): ?array
    {
        return null;
    }

    public function includedData(ResourceInterface $resource, array $data): array
    {
        return [];
    }

    public function meta(array $meta): array
    {
        return [];
    }

    public function paginator(PaginatorInterface $paginator): array
    {
        return [];
    }

    public function cursor(CursorInterface $cursor): array
    {
        return [];
    }
}
