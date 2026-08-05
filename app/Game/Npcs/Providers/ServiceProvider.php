<?php

namespace App\Game\Npcs\Providers;

use App\Flare\Pagination\Pagination;
use App\Game\Core\Chance\ChanceCalculator;
use App\Game\Core\Chance\RandomNumberGenerator;
use App\Game\Core\Items\Builders\AffixAttributeBuilder;
use App\Game\Core\Items\Builders\RandomAffixGenerator;
use App\Game\Core\Items\Transformers\CraftingItemPreviewTransformer;
use App\Game\Gems\Services\GemComparison;
use App\Game\Npcs\Actions\LabyrinthOracle\Services\ItemTransferService;
use App\Game\Npcs\Actions\QueenOfHearts\Services\QueenOfHeartsService;
use App\Game\Npcs\Actions\QueenOfHearts\Services\RandomEnchantmentService;
use App\Game\Npcs\Actions\QueenOfHearts\Services\ReRollEnchantmentService;
use App\Game\Npcs\Actions\QueenOfHearts\Transformers\QueenInventorySlotTransformer;
use App\Game\Npcs\Actions\Seer\Services\SeerService;
use App\Game\Npcs\Actions\Seer\Transformers\SeerGemTransformer;
use App\Game\Npcs\Actions\Seer\Transformers\SeerInventoryItemTransformer;
use App\Game\Npcs\Actions\WorkBench\Services\HolyItemService;
use Illuminate\Support\ServiceProvider as ApplicationServiceProvider;

class ServiceProvider extends ApplicationServiceProvider
{
    public function register(): void
    {
        $this->app->bind(ItemTransferService::class);
        $this->app->bind(HolyItemService::class);

        $this->app->bind(SeerService::class, fn ($app) => new SeerService(
            $app->make(GemComparison::class),
            $app->make(RandomNumberGenerator::class),
            $app->make(Pagination::class),
            $app->make(SeerInventoryItemTransformer::class),
            $app->make(SeerGemTransformer::class),
            $app->make(CraftingItemPreviewTransformer::class),
        ));
        $this->app->bind(RandomEnchantmentService::class, fn ($app) => new RandomEnchantmentService(
            $app->make(RandomAffixGenerator::class),
            $app->make(ChanceCalculator::class),
        ));
        $this->app->bind(ReRollEnchantmentService::class, fn ($app) => new ReRollEnchantmentService(
            $app->make(AffixAttributeBuilder::class),
            $app->make(RandomEnchantmentService::class),
        ));
        $this->app->bind(QueenOfHeartsService::class, fn ($app) => new QueenOfHeartsService(
            $app->make(RandomEnchantmentService::class),
            $app->make(ReRollEnchantmentService::class),
            $app->make(Pagination::class),
            $app->make(QueenInventorySlotTransformer::class),
            $app->make(CraftingItemPreviewTransformer::class),
        ));
    }
}
