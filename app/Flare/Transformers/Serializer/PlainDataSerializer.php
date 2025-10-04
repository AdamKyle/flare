<?php

namespace App\Flare\Transformers\Serializer;

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

    public function includedData(\League\Fractal\Resource\ResourceInterface $resource, array $data): array
    {
        return [];
    }

    public function meta(array $meta): array
    {
        return [];
    }

    public function paginator(\League\Fractal\Pagination\PaginatorInterface $paginator): array
    {
        return [];
    }

    public function cursor(\League\Fractal\Pagination\CursorInterface $cursor): array
    {
        return [];
    }
}
