<?php

namespace App\Flare\Transformers\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class CoreSerializer extends ArraySerializer
{
    /**
     * Sets a resource key on a collection if set.
     *
     * @param  mixed  $resourceKey
     */
    public function collection(?string $resourceKey, array $data): array
    {
        if ($resourceKey) {
            return [$resourceKey => $data];
        }

        return $data;
    }

    /**
     * Sets a resource key on a item if set.
     *
     * @param  mixed  $resourceKey
     */
    public function item(?string $resourceKey, array $data): array
    {
        if ($resourceKey) {
            return [$resourceKey => $data];
        }

        return $data;
    }
}
