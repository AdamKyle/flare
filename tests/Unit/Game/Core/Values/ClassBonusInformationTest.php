<?php

namespace Tests\Unit\Game\Core\Values;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\Core\Values\View\ClassBonusInformation;
use Tests\TestCase;
use Tests\Traits\CreateClass;

class ClassBonusInformationTest extends TestCase
{
    use RefreshDatabase, CreateClass;


    public function testGetInforForFighter() {
        $fighter = $this->createClass(['name' => 'Fighter']);

        $details = (new ClassBonusInformation())->buildClassBonusDetailsForInfo($fighter->name);

        $this->assertEquals('Dual Weapon equipped or Weapon/Shield equipped', $details['requires']);
    }

    public function testGetInforForRanger() {
        $ranger = $this->createClass(['name' => 'Ranger']);

        $details = (new ClassBonusInformation())->buildClassBonusDetailsForInfo($ranger->name);

        $this->assertEquals('Bow equipped', $details['requires']);
    }

    public function testGetInforForThief() {
        $thief = $this->createClass(['name' => 'Thief']);

        $details = (new ClassBonusInformation())->buildClassBonusDetailsForInfo($thief->name);

        $this->assertEquals('Dual weapons equipped', $details['requires']);
    }

    public function testGetInforForHeretic() {
        $heretic = $this->createClass(['name' => 'Heretic']);

        $details = (new ClassBonusInformation())->buildClassBonusDetailsForInfo($heretic->name);

        $this->assertEquals('Damage spell equipped', $details['requires']);
    }

    public function testGetInforForProphet() {
        $prophet = $this->createClass(['name' => 'Prophet']);

        $details = (new ClassBonusInformation())->buildClassBonusDetailsForInfo($prophet->name);

        $this->assertEquals('Healing spell equipped', $details['requires']);
    }

    public function testGetInforForVampire() {
        $vampire = $this->createClass(['name' => 'Vampire']);

        $details = (new ClassBonusInformation())->buildClassBonusDetailsForInfo($vampire->name);

        $this->assertEquals('N/A', $details['requires']);
    }
}
