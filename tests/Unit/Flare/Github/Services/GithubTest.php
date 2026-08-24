<?php

namespace Tests\Unit\Flare\Github\Services;

use App\Flare\Github\Services\Github;
use App\Flare\Github\Services\GithubClientFactory;
use Exception;
use Github\Api\Repo;
use Github\Api\Repository\Releases;
use Github\AuthMethod;
use Github\Client;
use Mockery;
use Tests\TestCase;

class GithubTest extends TestCase
{
    public function test_inject_client_returns_the_same_service_and_uses_the_injected_client(): void
    {
        $client = Mockery::mock(Client::class);
        $releases = Mockery::mock(Releases::class);
        $repo = Mockery::mock(Repo::class);

        $client->shouldReceive('api')->once()->with('repo')->andReturn($repo);
        $repo->shouldReceive('releases')->once()->andReturn($releases);
        $releases->shouldReceive('latest')->once()->with('AdamKyle', 'flare')->andReturn(['name' => 'v1.0.0']);

        $github = new Github(Mockery::mock(GithubClientFactory::class));
        $result = $github->injectClient($client);

        $this->assertSame($github, $result);
        $this->assertSame(['name' => 'v1.0.0'], $github->fetchLatestRelease());
    }

    public function test_unauthenticated_initiate_obtains_client_without_authenticating(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('authenticate');

        $clientFactory = Mockery::mock(GithubClientFactory::class);
        $clientFactory->shouldReceive('create')->once()->andReturn($client);

        $github = (new Github($clientFactory))->initiateClient();

        $this->assertInstanceOf(Github::class, $github);
    }

    public function test_authenticated_initiate_calls_authenticate_once_with_the_configured_token(): void
    {
        config(['github.token' => 'test-token']);

        $client = Mockery::mock(Client::class);
        $client->shouldReceive('authenticate')->once()->with('test-token', AuthMethod::ACCESS_TOKEN);

        $clientFactory = Mockery::mock(GithubClientFactory::class);
        $clientFactory->shouldReceive('create')->once()->andReturn($client);

        $github = (new Github($clientFactory))->initiateClient(true);

        $this->assertInstanceOf(Github::class, $github);
    }

    public function test_fetch_latest_release_before_initialization_throws(): void
    {
        $github = new Github(Mockery::mock(GithubClientFactory::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Client is not initiated. Please call initiateClient first');

        $github->fetchLatestRelease();
    }

    public function test_fetch_all_releases_before_initialization_throws(): void
    {
        $github = new Github(Mockery::mock(GithubClientFactory::class));

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Client is not initiated. Please call initiateClient first');

        $github->fetchAllReleases();
    }

    public function test_fetch_latest_release_delegates_to_adam_kyle_flare(): void
    {
        $client = Mockery::mock(Client::class);
        $releases = Mockery::mock(Releases::class);
        $repo = Mockery::mock(Repo::class);

        $client->shouldReceive('api')->once()->with('repo')->andReturn($repo);
        $repo->shouldReceive('releases')->once()->andReturn($releases);
        $releases->shouldReceive('latest')->once()->with('AdamKyle', 'flare')->andReturn(['name' => 'v2.0.0']);

        $github = (new Github(Mockery::mock(GithubClientFactory::class)))->injectClient($client);

        $this->assertSame(['name' => 'v2.0.0'], $github->fetchLatestRelease());
    }

    public function test_fetch_all_releases_delegates_to_adam_kyle_flare_with_per_page_100(): void
    {
        $client = Mockery::mock(Client::class);
        $releases = Mockery::mock(Releases::class);
        $repo = Mockery::mock(Repo::class);

        $client->shouldReceive('api')->once()->with('repo')->andReturn($repo);
        $repo->shouldReceive('releases')->once()->andReturn($releases);
        $releases->shouldReceive('all')->once()->with('AdamKyle', 'flare', ['per_page' => 100])->andReturn([['name' => 'v2.0.0'], ['name' => 'v1.0.0']]);

        $github = (new Github(Mockery::mock(GithubClientFactory::class)))->injectClient($client);

        $this->assertSame([['name' => 'v2.0.0'], ['name' => 'v1.0.0']], $github->fetchAllReleases());
    }
}
