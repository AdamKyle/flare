<?php

namespace App\Flare\Github\Services;

use Exception;
use Github\AuthMethod;
use Github\Client;

class Github
{
    private ?Client $client = null;

    /**
     * Inject a client object.
     */
    public function injectClient(Client $client): Github
    {
        $this->client = $client;

        return $this;
    }

    /**
     * Initiate the client, either with or without authentication.
     *
     * @return $this
     */
    public function initiateClient(bool $withAuth = false): Github
    {
        $client = new Client;

        if ($withAuth) {
            $client->authenticate(config('github.token'), AuthMethod::ACCESS_TOKEN);
        }

        $this->client = $client;

        return $this;
    }

    /**
     * Fetch the latest release.
     *
     * @throws Exception
     */
    public function fetchLatestRelease(): array
    {
        if (is_null($this->client)) {
            throw new Exception('Client is not initiated. Please call initiateClient first');
        }

        return $this->client->api('repo')->releases()->latest('AdamKyle', 'flare');
    }

    /**
     * Fetch all releases from github.
     *
     * @throws Exception
     */
    public function fetchAllReleases(): array
    {
        if (is_null($this->client)) {
            throw new Exception('Client is not initiated. Please call initiateClient first');
        }

        return $this->client->api('repo')->releases()->all('AdamKyle', 'flare', [
            'per_page' => 100,
        ]);
    }
}
