<?php

namespace Tests\Unit\Flare\Github\Services;

use Exception;
use Mockery;
use Github\Api\Repo;
use Github\AuthMethod;
use Github\Client;
use Github\Api\Repository\Releases;
use Illuminate\Support\Facades\Config;
use App\Flare\Github\Services\Github;
use Tests\TestCase;

class GithubTest extends TestCase {

    private ?Github $github;

    public function setUp(): void {
        parent::setUp();

        $this->github = resolve(Github::class);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->github = null;
    }

    public function testInitializeGitHUbWithOutAuthorization() {
        Mockery::mock(Client::class);

        $result = $this->github->initiateClient();

        $this->assertInstanceOf(Github::class, $result);
    }

    public function testInitializeGitHUbWithAuthorization() {
        Config::set('github.token', 1234567890);

        $mock = Mockery::mock(Client::class)->makePartial();

        $mock->shouldReceive('authenticate')->with('1234567890', AuthMethod::ACCESS_TOKEN);

        $result = $this->github->initiateClient(true);

        $this->assertInstanceOf(Github::class, $result);
    }

    public function testBailWhenClientNotSet() {

        $this->expectException(Exception::class);

        $this->github->fetchLatestRelease();
    }

    public function testGetReleaseDataFromGithub() {
        $mock     = Mockery::mock(Client::class);
        $repo     = Mockery::mock(Repo::class);
        $releases = Mockery::mock(Releases::class);

        $mock->shouldReceive('api')->with('repo')->andReturn($repo);
        $repo->shouldReceive('releases')->andReturn($releases);
        $releases->shouldReceive('latest')->andReturn([]);

        $result = $this->github->injectClient($mock)->fetchLatestRelease();

        $this->assertEmpty($result);
    }
}
