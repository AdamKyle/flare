<?php

namespace App\Flare\Github\Services;

use Github\Client;

class GithubClientFactory
{
    /**
     * Create a new, unauthenticated Github API client instance.
     */
    public function create(): Client
    {
        return new Client;
    }
}
