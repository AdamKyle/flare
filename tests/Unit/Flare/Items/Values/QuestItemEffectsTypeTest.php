<?php

namespace Tests\Unit\Flare\Items\Values;

use App\Flare\Items\Values\QuestItemEffectsType;
use PHPUnit\Framework\TestCase;

class QuestItemEffectsTypeTest extends TestCase
{
    public function testLabelReturnsExpectedTextForEachCase(): void
    {
        $expected = [
            'walk-on-water'                     => 'Walk on water (Surface and Labyrinth)',
            'walk-on-ice'                       => 'Walk on Ice (The Ice Plane)',
            'labyrinth'                         => 'Use Traverse (beside movement map-actions) to traverse to Labyrinth plane',
            'dungeon'                           => 'Use Traverse (beside movement map-actions) to traverse to Dungeons plane',
            'shadow-plane'                      => 'Use Traverse (beside movement map-actions) to traverse to Shadow Plane',
            'hell'                              => 'Use Traverse (beside movement map-actions) to traverse to Hell plane',
            'purgatory'                         => 'Use Traverse (beside movement map-actions) to traverse to Purgatory plane (only while in Hell at Tear in the Fabric of Time: X/Y 208/64)',
            'mass-embezzle'                     => 'Lets you mass embezzle from all kingdoms on the plane. Go to Kingdoms → select a kingdom → Mass Embezzle (not cross-plane).',
            'walk-on-magma'                     => 'Lets you walk on Magma in Hell.',
            'affixes-irresistible'              => 'Makes affix damage irresistible except in Hell and Purgatory.',
            'speak-to-queen-of-hearts'          => 'Lets a character approach and speak to the Queen of Hearts in Hell.',
            'gold-dust-rush'                    => 'Provides a small chance to get a gold dust rush when disenchanting.',
            'walk-on-death-water'               => 'Walk on Death Water in Dungeons Plane.',
            'teleport-to-celestial'             => 'Use /pct to find and teleport/traverse to the public Celestial Entity.',
            'effects-faction-points'            => 'Gain 10 faction points per kill starting at level one of the faction.',
            'get-copper-coins'                  => 'Enemies in Purgatory drop copper coins relative to their gold (random 5–20 per battle).',
            'enter-purgatory-house'             => 'Enter the Purgatory Smith house to investigate the Green Growing Light.',
            'hide-chat-location'                => 'Hides your location from chat so others cannot find and duel you.',
            'settle-on-the-ice-plane'           => 'Allows you to settle kingdoms on The Ice Plane.',
            'the-old-church'                    => 'Gain currency bonuses and uniques at The Old Church on the Ice Plane during the Winter Event.',
            'mercenary-slot-bonus'              => 'Gain +50% slot-machine currency rewards and +5% Copper Coins in Purgatory Dungeons.',
            'walk-on-delusional-memories-water' => 'Walk on water on the Delusional Memories plane.',
            'access-twisted-memories'           => 'Access the Twisted Dimensional Gate in Hell to enter Twisted Memories.',
            'twisted-dungeons'                  => 'Access the Dungeons of twisted maidens in Twisted Memories.',
            'continue-leveling'                 => 'Continue leveling.',
        ];

        $caseValues = array_map(static fn($c) => $c->value, QuestItemEffectsType::cases());
        $expectedKeys = array_keys($expected);

        sort($caseValues);
        sort($expectedKeys);

        $this->assertSame($caseValues, $expectedKeys, 'Expected labels must cover all enum values.');

        foreach (QuestItemEffectsType::cases() as $case) {
            $this->assertArrayHasKey($case->value, $expected);
            $this->assertSame($expected[$case->value], $case->label(), "Label mismatch for {$case->name}");
        }
    }
}
