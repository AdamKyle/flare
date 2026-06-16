<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class BeastmasterClassViewTest extends TestCase
{
    use CreateClass, RefreshDatabase;

    public function testBeastmasterClassPageShowsPrimaryRequiredClass(): void
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

    public function testBeastmasterClassPageShowsSecondaryRequiredClass(): void
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

    public function testBeastmasterClassPageShowsBowsAndHammers(): void
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

    public function testBeastmasterClassPageShowsDevilsPiercingShotSpecial(): void
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

    public function testBeastmasterClassPageShowsBeastStompSpecial(): void
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

    public function testBeastmasterClassPageShowsBleedPercentages(): void
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
