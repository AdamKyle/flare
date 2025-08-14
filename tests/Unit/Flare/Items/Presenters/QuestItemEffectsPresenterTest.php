<?php

namespace Tests\Unit\Flare\Items\Presenters;

use App\Flare\Items\Presenters\QuestItemEffectsPresenter;
use App\Flare\Items\Values\QuestItemEffectsType;
use PHPUnit\Framework\TestCase;

final class QuestItemEffectsPresenterTest extends TestCase
{
    public function testReturnsNAForNull(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame('N/A', $presenter->getEffect(null));
    }

    public function testReturnsNAForEmptyString(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame('N/A', $presenter->getEffect(''));
    }

    public function testReturnsNAForInvalidValue(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame('N/A', $presenter->getEffect('not-a-real-effect'));
    }

    public function testReturnsLabelForAllEnumCases(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        foreach (QuestItemEffectsType::cases() as $case) {
            $expected = $case->label();
            $this->assertSame(
                $expected,
                $presenter->getEffect($case->value),
                "Label mismatch for {$case->name}"
            );
        }
    }
}
