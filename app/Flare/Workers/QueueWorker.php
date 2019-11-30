<?php

namespace App\Flare\Workers;

use Illuminate\Queue\Worker;

class QueueWorker extends Worker {

    /**
     * Sleep the script for a given number of seconds.
     *
     * @param int $seconds
     * @return void
     */
    public function sleep($seconds) {
        usleep(floatval($seconds) * 1e6);
    }
}
