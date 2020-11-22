<?php

namespace App\Flare\Transformers\Serializers;

use League\Fractal\Serializer\ArraySerializer;

class CoreSerializer extends ArraySerializer
{
    /**
     * Sets a resource key on a collection if set.
     * 
     * @param mixed $resourceKey
     * @param array $data
     */
    public function collection($resourceKey, array $data) {
        if ($resourceKey) {
            return [$resourceKey => $data];
        }

        return $data;
    }

    /**
     * Sets a resource key on a item if set.
     * 
     * @param mixed $resourceKey
     * @param array $data
     */
    public function item($resourceKey, array $data) {
        if ($resourceKey) {
            return [$resourceKey => $data];
        }
        return $data;
    }
}