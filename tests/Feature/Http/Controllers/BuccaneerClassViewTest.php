<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class BuccaneerClassViewTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function test_buccaneer_class_page_does_not_show_primary_required_class(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('Primary Required Class');
    }

    public function test_buccaneer_class_page_does_not_show_secondary_required_class(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('Secondary Required Class');
    }

    public function test_buccaneer_class_page_does_not_show_required_class_levels(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('Primary Required Class Level');
        $response->assertDontSee('Secondary Required Class Level');
    }

    public function test_buccaneer_class_page_shows_guns_and_shields(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('Guns and Shields');
    }

    public function test_buccaneer_class_page_shows_gun_and_shield_special_details(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('25%');
        $response->assertSee('15%');
        $response->assertSee('5%');
    }

    public function test_buccaneer_class_page_shows_dual_guns_special_details(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('75%');
        $response->assertSee('55%');
        $response->assertSee('35%');
    }

    public function test_buccaneer_class_page_does_not_say_dual_guns_are_planned(): void
    {
        $class = $this->createClass(['name' => 'Buccaneer', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertDontSee('planned');
        $response->assertDontSee('intended for');
    }
}
