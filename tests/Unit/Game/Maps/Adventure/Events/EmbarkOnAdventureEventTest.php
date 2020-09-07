<?php

namespace Tests\Unit\Game\Maps\Adventure\Events;

use App\Flare\Events\ServerMessageEvent;
use App\Game\Maps\Adventure\Events\EmbarkOnAdventureEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use App\Game\Maps\Adventure\Events\MoveTimeOutEvent;
use App\Game\Maps\Adventure\Events\ShowTimeOutEvent;
use App\Game\Maps\Adventure\Events\UpdateAdventureLogsBroadcastEvent;
use App\Game\Maps\Adventure\Listeners\EmbarkOnAdventureListener;
use Cache;
use Queue;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateAdventure;
use Tests\Setup\CharacterSetup;

class EmbarkOnAdventureEventTest extends TestCase
{
    use RefreshDatabase, CreateUser, CreateAdventure;


    public function testAllLevelsAtATime()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        $character = (new CharacterSetup)->setupCharacter($user)
                                         ->createAdventureLog($adventure)
                                         ->setSkill('Accuracy', [
                                                'bonus' => 10,
                                                'xp_towards' => 10,
                                            ], true)
                                         ->setSkill('Dodge', [
                                                'bonus' => 10,
                                            ])
                                         ->setSkill('Looting', [
                                                'bonus' => 0,
                                            ])
                                         ->getCharacter();
        
        Queue::fake();

        Event::fake([ServerMessageEvent::class, UpdateAdventureLogsBroadcastEvent::class]);

        event(new EmbarkOnAdventureEvent($character, $adventure, 'all'));

        $character->refresh();

        $this->assertNotNull($character->refresh()->can_adventure_again_at);
        $this->assertNotNull(Cache::get('character_'.$character->id.'_adventure_'.$adventure->id));
    }

    public function testInvalidLevelsAtATime()
    {
        $user = $this->createUser();

        $adventure = $this->createNewAdventure();

        $character = (new CharacterSetup)->setupCharacter($user)
                                         ->createAdventureLog($adventure, [
                                             'in_progress' => true
                                         ])
                                         ->setSkill('Accuracy', [
                                                'bonus' => 10,
                                                'xp_towards' => 10,
                                            ], true)
                                         ->setSkill('Dodge', [
                                                'bonus' => 10,
                                            ])
                                         ->setSkill('Looting', [
                                                'bonus' => 0,
                                            ])
                                         ->getCharacter();
        
        Queue::fake();

        Event::fake([ServerMessageEvent::class, UpdateAdventureLogsBroadcastEvent::class]);

        event(new EmbarkOnAdventureEvent($character, $adventure, 100));

        $character->refresh();

        $this->assertNull($character->refresh()->can_adventure_again_at);
        $this->assertNull(Cache::get('character_'.$character->id.'_adventure_'.$adventure->id));
        $this->assertFalse($character->refresh()->adventureLogs->first()->in_progress);
    }
}
