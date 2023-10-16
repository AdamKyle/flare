<?php

namespace App\Flare\Github\Services;

use Exception;
use Github\Client;
use Github\AuthMethod;

class Github {

    /**
     * @var Client|null $client
     */
    private ?Client $client = null;

    /**
     * Inject a client object.
     *
     * @param Client $client
     * @return Github
     */
    public function injectClient(Client $client): Github {
        $this->client = $client;

        return $this;
    }

    /**
     * Initiate the client, either with or without authentication.
     *
     * @param bool $withAuth
     * @return $this
     */
    public function initiateClient(bool $withAuth = false): Github {
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
     * @return array
     * @throws Exception
     */
    public function fetchLatestRelease(): array {
        if (is_null($this->client)) {
            throw new Exception('Client is not initiated. Please call initiateClient first');
        }

        return $this->client->api('repo')->releases()->latest('AdamKyle', 'flare');
    }

    /**
     * Fetch all releases from github.
     *
     * @return array
     * @throws Exception
     */
    public function fetchAllReleases(): array {
        if (is_null($this->client)) {
            throw new Exception('Client is not initiated. Please call initiateClient first');
        }

        return $this->client->api('repo')->releases()->all('AdamKyle', 'flare');
    }
}
