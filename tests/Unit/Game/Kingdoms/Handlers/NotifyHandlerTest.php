<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Mail\GenericMail;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Handlers\NotifyHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class NotifyHandlerTest extends TestCase {

    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testUnitsReturningFromYourOwnKingdom() {
        Mail::fake();

        $kingdom = (new CharacterFactory())->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->kingdomManagement()
                                           ->assignKingdom()
                                           ->assignUnits()
                                           ->assignBuilding();

        $notifyHandler = resolve(NotifyHandler::class);

        $notifyHandler->setAttackingKingdom($kingdom->getKingdom())
                      ->setDefendingKingdom($kingdom->getKingdom())
                      ->setDefendingCharacter($kingdom->getCharacter(false))
                      ->setOldDefendingKingdom($kingdom->getKingdom()->toArray())
                      ->setNewDefendingKingdom($kingdom->getKingdom())
                      ->setSentUnits([])
                      ->setSurvivingUnits([])
                      ->notifyAttacker(KingdomLogStatusValue::UNITS_RETURNING, $kingdom->getKingdom(), $kingdom->getCharacter(false));

        Mail::assertSent(GenericMail::class);
    }

    public function testUnitsReturningFromYourOwnKingdomWhenOnLine() {
        Mail::fake();
        Event::fake();

        $kingdom = (new CharacterFactory())->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignUnits()
            ->assignBuilding();

        DB::table('sessions')->insert([[
            'id'           => '1',
            'user_id'      => $kingdom->getUser()->id,
            'ip_address'   => '1',
            'user_agent'   => '1',
            'payload'      => '1',
            'last_activity'=> 1602801731,
        ]]);

        $notifyHandler = resolve(NotifyHandler::class);

        $notifyHandler->setAttackingKingdom($kingdom->getKingdom())
            ->setDefendingKingdom($kingdom->getKingdom())
            ->setDefendingCharacter($kingdom->getCharacter(false))
            ->setOldDefendingKingdom($kingdom->getKingdom()->toArray())
            ->setNewDefendingKingdom($kingdom->getKingdom())
            ->setSentUnits([])
            ->setSurvivingUnits([])
            ->notifyAttacker(KingdomLogStatusValue::UNITS_RETURNING, $kingdom->getKingdom(), $kingdom->getCharacter(false));

        Mail::assertNothingSent();
        Event::assertDispatched(KingdomServerMessageEvent::class);
    }
}
