<?php

namespace Tests\Unit\Flare\Github\Services;

use App\Flare\Github\Services\Github;
use Exception;
use Github\Api\Repo;
use Github\Api\Repository\Releases;
use Github\AuthMethod;
use Github\Client;
use Illuminate\Support\Facades\Config;
use Mockery;
use Tests\TestCase;

class GithubTest extends TestCase
{
    private ?Github $github;

    protected function setUp(): void
    {
        parent::setUp();

        $this->github = resolve(Github::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->github = null;
    }

    public function test_initialize_git_h_ub_with_out_authorization()
    {
        Mockery::mock(Client::class);

        $result = $this->github->initiateClient();

        $this->assertInstanceOf(Github::class, $result);
    }

    public function test_initialize_git_h_ub_with_authorization()
    {
        Config::set('github.token', 1234567890);

        $mock = Mockery::mock(Client::class)->makePartial();

        $mock->shouldReceive('authenticate')->with('1234567890', AuthMethod::ACCESS_TOKEN);

        $result = $this->github->initiateClient(true);

        $this->assertInstanceOf(Github::class, $result);
    }

    public function test_bail_when_client_not_set()
    {

        $this->expectException(Exception::class);

        $this->github->fetchLatestRelease();
    }

    public function test_get_release_data_from_github()
    {
        $mock = Mockery::mock(Client::class);
        $repo = Mockery::mock(Repo::class);
        $releases = Mockery::mock(Releases::class);

        $mock->shouldReceive('api')->with('repo')->andReturn($repo);
        $repo->shouldReceive('releases')->andReturn($releases);
        $releases->shouldReceive('latest')->andReturn([]);

        $result = $this->github->injectClient($mock)->fetchLatestRelease();

        $this->assertEmpty($result);
    }
}
