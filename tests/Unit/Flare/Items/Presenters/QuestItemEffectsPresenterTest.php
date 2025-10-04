<?php

namespace Tests\Unit\Flare\Items\Presenters;

use App\Flare\Items\Presenters\QuestItemEffectsPresenter;
use App\Flare\Items\Values\QuestItemEffectsType;
use PHPUnit\Framework\TestCase;

final class QuestItemEffectsPresenterTest extends TestCase
{
    public function test_returns_na_for_null(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame('N/A', $presenter->getEffect(null));
    }

    public function test_returns_na_for_empty_string(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame('N/A', $presenter->getEffect(''));
    }

    public function test_returns_na_for_invalid_value(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame('N/A', $presenter->getEffect('not-a-real-effect'));
    }

    public function test_returns_label_for_all_enum_cases(): void
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
