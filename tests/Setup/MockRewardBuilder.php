<?php

namespace Tests\Setup;

use App\Game\Adventures\Builders\RewardBuilder;
use Tests\Traits\CreateItem;

class MockRewardBuilder
{
    use CreateItem;

    public function mockRewardBuilder($app)
    {
        $rewardBuilder = \Mockery::mock(RewardBuilder::class)->makePartial();

        $app->instance(RewardBuilder::class, $rewardBuilder);

        $rewardBuilder->shouldReceive('fetchDrops')->withAnyArgs()->andReturn(
            $this->createItem()
        );

        $rewardBuilder->shouldReceive('fetchQuestItemFromMonster')->withAnyArgs()->andReturn(
            $this->createItem(['type' => 'quest'])
        );
    }
}
