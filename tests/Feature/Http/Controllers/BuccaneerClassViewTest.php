<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class BuccaneerClassViewTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function testBuccaneerClassPageDoesNotShowPrimaryRequiredClass(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('Primary Required Class');
    }

    public function testBuccaneerClassPageDoesNotShowSecondaryRequiredClass(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('Secondary Required Class');
    }

    public function testBuccaneerClassPageDoesNotShowRequiredClassLevels(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('Primary Required Class Level');
        $response->assertDontSee('Secondary Required Class Level');
    }

    public function testBuccaneerClassPageShowsGunsAndShields(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('Guns and Shields');
    }

    public function testBuccaneerClassPageShowsGunAndShieldSpecialDetails(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('25%');
        $response->assertSee('15%');
        $response->assertSee('5%');
    }

    public function testBuccaneerClassPageShowsDualGunsSpecialDetails(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('75%');
        $response->assertSee('55%');
        $response->assertSee('35%');
    }

    public function testBuccaneerClassPageDoesNotSayDualGunsArePlanned(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('planned');
        $response->assertDontSee('intended for');
    }
}
