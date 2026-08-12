<?php

namespace Tests\Traits;

use App\Flare\Models\ItemSocket;

trait CreateItemSocket
{
    public function createItemSocket(array $options = []): ItemSocket
    {
        return ItemSocket::create($options);
    }
}
