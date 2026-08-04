<?php

namespace Tests\Unit\Game\Core\Currency;

use App\Game\Core\Currency\Services\CurrencyLimit;
use App\Game\Core\Currency\Values\CurrencyType;
use PHPUnit\Framework\TestCase;

final class CurrencyLimitTest extends TestCase
{
    public function test_gold_limit_is_preserved(): void
    {
        $this->assertFalse((new CurrencyLimit(CurrencyLimit::MAX_GOLD - 1, CurrencyType::GOLD))->canNotGiveCurrency());
        $this->assertTrue((new CurrencyLimit(CurrencyLimit::MAX_GOLD, CurrencyType::GOLD))->canNotGiveCurrency());
    }

    public function test_gold_dust_limit_is_preserved(): void
    {
        $this->assertFalse((new CurrencyLimit(CurrencyLimit::MAX_GOLD_DUST - 1, CurrencyType::GOLD_DUST))->canNotGiveCurrency());
        $this->assertTrue((new CurrencyLimit(CurrencyLimit::MAX_GOLD_DUST, CurrencyType::GOLD_DUST))->canNotGiveCurrency());
    }

    public function test_shards_limit_is_preserved(): void
    {
        $this->assertFalse((new CurrencyLimit(CurrencyLimit::MAX_SHARDS - 1, CurrencyType::SHARDS))->canNotGiveCurrency());
        $this->assertTrue((new CurrencyLimit(CurrencyLimit::MAX_SHARDS, CurrencyType::SHARDS))->canNotGiveCurrency());
    }

    public function test_copper_limit_is_preserved(): void
    {
        $this->assertFalse((new CurrencyLimit(CurrencyLimit::MAX_COPPER - 1, CurrencyType::COPPER))->canNotGiveCurrency());
        $this->assertTrue((new CurrencyLimit(CurrencyLimit::MAX_COPPER, CurrencyType::COPPER))->canNotGiveCurrency());
    }
}
