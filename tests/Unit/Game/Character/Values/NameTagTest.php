<?php

namespace Tests\Unit\Game\Character\Values;

use App\Game\Character\Values\NameTag;
use Tests\TestCase;

class NameTagTest extends TestCase
{
    public function test_label_returns_the_expected_text_for_each_tag(): void
    {
        $this->assertSame('Slayer of the Queen of Ice', NameTag::ICE_QUEEN_SLAYER->label());
        $this->assertSame('Explorer of Tlessa', NameTag::EXPLORER->label());
        $this->assertSame('Ruler of Tlessa', NameTag::RULER->label());
        $this->assertSame('Twisted Demon Slayer of Galidoth', NameTag::DEMON_SLAYER->label());
        $this->assertSame('Lover to the Queen of Hearts', NameTag::QUEEN_OF_HEARTS->label());
        $this->assertSame('Gambling Addict', NameTag::GAMBLING_ADDICT->label());
        $this->assertSame('Savage Earth Eater', NameTag::EARTH_EATER->label());
        $this->assertSame('A Deranged Lunitic From Hell', NameTag::DERANGED_LUNITIC_OF_HELL->label());
        $this->assertSame('Special helper to Mr. Whiskers', NameTag::HELPER_OF_MR_WHISKERS->label());
        $this->assertSame('All your bases belong to us', NameTag::ALL_YOUR_BASES_BELONG_TO_US->label());
        $this->assertSame('Most feared magi in all of recent memory', NameTag::FEARSOME_MAGI_OF_THE_MEMORY->label());
    }

    public function test_options_returns_every_case_keyed_by_value_with_its_label(): void
    {
        $options = NameTag::options();

        $this->assertCount(count(NameTag::cases()), $options);
        $this->assertSame('Explorer of Tlessa', $options['explorer']);
        $this->assertSame('Most feared magi in all of recent memory', $options['fearsome-magi-of-the-memory']);
    }
}
