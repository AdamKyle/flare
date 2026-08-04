<?php

namespace Tests\Unit\Game\Core\Items\Presenters;

use App\Game\Core\Items\Presenters\QuestItemEffectsPresenter;
use App\Game\Core\Items\Values\ItemEffectType;
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

    public function test_returns_item_effect_type_label(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame(ItemEffectType::WALK_ON_WATER->label(), $presenter->getEffect(ItemEffectType::WALK_ON_WATER->value));
    }

    public function test_returns_label_for_effect_merged_from_item_effect_type(): void
    {
        $presenter = new QuestItemEffectsPresenter();

        $this->assertSame(ItemEffectType::DELVE->label(), $presenter->getEffect(ItemEffectType::DELVE->value));
    }
}
