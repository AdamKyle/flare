<?php

namespace App\Admin\Transformers;

use App\Flare\Transformers\BaseTransformer;

class AdminLogFileTransformer extends BaseTransformer
{
    public function transform(array $file): array
    {
        return [
            'key' => $file['key'],
            'label' => $file['label'],
            'exists' => (bool) $file['exists'],
            'size_bytes' => (int) $file['size_bytes'],
            'files' => $file['files'],
        ];
    }
}
