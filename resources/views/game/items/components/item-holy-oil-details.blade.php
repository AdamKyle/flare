<div>
    @php
        switch ($item->holy_level) {
            case 1:
                $statIncrease = '1-3';
                break;
            case 2:
                $statIncrease = '1-5';
                break;
            case 3:
                $statIncrease = '1-8';
                break;
            case 4:
                $statIncrease = '1-10';
                break;
            case 5:
                $statIncrease = '1-15';
                break;
            default:
                $statIncrease = 'ERROR';
        }

        switch ($item->holy_level) {
            case 1:
                $devouringIncrease = '0.1-0.3';
                break;
            case 2:
                $devouringIncrease = '0.1-0.5';
                break;
            case 3:
                $devouringIncrease = '0.1-0.8';
                break;
            case 4:
                $devouringIncrease = '0.1-1';
                break;
            case 5:
                $devouringIncrease = '0.1-1.5';
                break;
            default:
                $devouringIncrease = 'ERROR';
        }
    @endphp

    <x-core.cards.card-with-title title="Details" buttons="false">
        <p class="my-4 text-sky-600 dark:text-sky-400">
            {{ nl2br($item->description) }}
        </p>

        <dl>
            <dt>Holy Level</dt>
            <dd>{{ $item->holy_level }}</dd>
            <dt>Stat Increase per use</dt>
            <dd>{{ $statIncrease }}%</dd>
            <dt>Devouring Light/Dark Resistance per use</dt>
            <dd>{{ $devouringIncrease }}%</dd>
        </dl>
    </x-core.cards.card-with-title>

    <x-core.cards.card css="mb-4">
        <div class="grid md:grid-cols-2 gap-3">
            <div>
                <strong>Crafting Information</strong>
                <div
                    class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                ></div>
                <dl>
                    <dt>Skill Required</dt>
                    <dd>
                        @if ($item->crafting_type !== 'trinketry' || $item->crafting_type !== 'alchemy')
                            {{ ucfirst($item->crafting_type) }}
                        @else
                            {{ ucfirst($item->crafting_type) }} Crafting
                        @endif
                    </dd>
                    <dt>Skill Level Required</dt>
                    <dd>{{ $item->skill_level_required }}</dd>
                    <dt>Becomes Trivial at (no XP)</dt>
                    <dd>{{ $item->skill_level_trivial }}</dd>
                </dl>
            </div>
            <div
                class="block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
            ></div>
            <div>
                <strong>Crafting Cost</strong>
                <div
                    class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                ></div>
                <dl>
                    @if (! is_null($item->gold_cost) || $item->gold_cost > 0)
                        <dt>Gold Cost</dt>
                        <dd>{{ number_format($item->gold_cost) }}</dd>
                    @endif

                    @if (! is_null($item->gold_dust_cost) || $item->gold_dust_cost > 0)
                        <dt>Gold Dust Cost</dt>
                        <dd>{{ number_format($item->gold_dust_cost) }}</dd>
                    @endif

                    @if (! is_null($item->shards_cost) || $item->shards_cost > 0)
                        <dt>Gold Cost</dt>
                        <dd>{{ number_format($item->shards_cost) }}</dd>
                    @endif

                    @if (! is_null($item->copper_coin_cost) || $item->copper_coin_cost > 0)
                        <dt>Gold Cost</dt>
                        <dd>{{ number_format($item->copper_coin_cost) }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </x-core.cards.card>
</div>
