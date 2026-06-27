<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class BeastmasterClassViewTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function test_beastmaster_class_page_shows_primary_required_class(): void
    {
        $primaryClass = $this->createClass(['name' => 'Ranger', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        $secondaryClass = $this->createClass(['name' => 'Blacksmith', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $class = $this->createClass([
            'name' => 'Beastmaster',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'primary_required_class_id' => $primaryClass->id,
            'secondary_required_class_id' => $secondaryClass->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('Primary Required Class');
    }

    public function test_beastmaster_class_page_shows_secondary_required_class(): void
    {
        $primaryClass = $this->createClass(['name' => 'Ranger', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        $secondaryClass = $this->createClass(['name' => 'Blacksmith', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $class = $this->createClass([
            'name' => 'Beastmaster',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'primary_required_class_id' => $primaryClass->id,
            'secondary_required_class_id' => $secondaryClass->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('Secondary Required Class');
    }

    public function test_beastmaster_class_page_shows_bows_and_hammers(): void
    {
        $primaryClass = $this->createClass(['name' => 'Ranger', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        $secondaryClass = $this->createClass(['name' => 'Blacksmith', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $class = $this->createClass([
            'name' => 'Beastmaster',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'primary_required_class_id' => $primaryClass->id,
            'secondary_required_class_id' => $secondaryClass->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('Bows and Hammers');
    }

    public function test_beastmaster_class_page_shows_devils_piercing_shot_special(): void
    {
        $primaryClass = $this->createClass(['name' => 'Ranger', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        $secondaryClass = $this->createClass(['name' => 'Blacksmith', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $class = $this->createClass([
            'name' => 'Beastmaster',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'primary_required_class_id' => $primaryClass->id,
            'secondary_required_class_id' => $secondaryClass->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee("Devil's Piercing Shot", false);
    }

    public function test_beastmaster_class_page_shows_beast_stomp_special(): void
    {
        $primaryClass = $this->createClass(['name' => 'Ranger', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        $secondaryClass = $this->createClass(['name' => 'Blacksmith', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $class = $this->createClass([
            'name' => 'Beastmaster',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'primary_required_class_id' => $primaryClass->id,
            'secondary_required_class_id' => $secondaryClass->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('Beast Stomp');
    }

    public function test_beastmaster_class_page_shows_bleed_percentages(): void
    {
        $primaryClass = $this->createClass(['name' => 'Ranger', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);
        $secondaryClass = $this->createClass(['name' => 'Blacksmith', 'damage_stat' => 'str', 'to_hit_stat' => 'dex']);

        $class = $this->createClass([
            'name' => 'Beastmaster',
            'damage_stat' => 'str',
            'to_hit_stat' => 'dex',
            'primary_required_class_id' => $primaryClass->id,
            'secondary_required_class_id' => $secondaryClass->id,
            'primary_required_class_level' => 10,
            'secondary_required_class_level' => 10,
        ]);

        $response = $this->call('GET', route('info.page.class', ['class' => $class]));

        $response->assertOk();
        $response->assertSee('17%');
        $response->assertSee('14%');
        $response->assertSee('8%');
        $response->assertSee('4%');
    }
}
