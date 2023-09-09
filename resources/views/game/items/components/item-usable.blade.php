<x-core.cards.card-with-title title="Details" buttons="false">
    <p class="my-4 text-sky-600 dark:text-sky-400">
        {{ nl2br($item->description) }}
    </p>

    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

    <dl>
        <dt>Can Stack? (Allows you to use multiple at once)</dt>
        <dd>{{ $item->can_stack ? 'Yes' : 'No' }}</dd>
        <dt>Lasts for (Minutes)</dt>
        <dd>{{ $item->lasts_for }}</dd>
    </dl>

    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

    <dl>
        <dt>Xp bonus per kill</dt>
        <dd>{{ $item->xp_bonus * 100 }}%</dd>
        <dt>Gain additional level on level up?</dt>
        <dd>{{ $item->gains_additional_level ? 'Yes' : 'No' }}</dd>
    </dl>

    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

    <div class="grid md:grid-cols-3 gap-3">
        <div>
            <strong>Stats</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>All Stat increase %</dt>
                <dd>{{ $item->increase_stat_by * 100, 2 }}%</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Modifiers</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <dl>
                <dt>Base Damage Mod</dt>
                <dd>{{ $item->base_damage_mod * 100 }} %</dd>
                <dt>Base Ac Mod</dt>
                <dd>{{ $item->base_ac_mod * 100 }} %</dd>
                <dt>Base Healing Mod</dt>
                <dd>{{ $item->base_healing_mod * 100 }} %</dd>
            </dl>
        </div>
        <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div>
            <strong>Skill Modifiers</strong>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            @if ($item->type === 'alchemy')
                <dl>
                    <dt>Effects Skill Type<sup>*</sup></dt>
                    <dd>{{ !is_null($item->affects_skill_type) ? implode(',', $skills) : 'N/A' }}</dd>
                    <dt>Skill Bonus</dt>
                    <dd>{{ $item->increase_skill_bonus_by * 100 }}%</dd>
                    <dt>Skill XP Bonus</dt>
                    <dd>{{ $item->increase_skill_training_bonus_by * 100 }}%</dd>
                </dl>
                <p class="my-4">
                    <sup>*</sup> Type of skill refers to the category of skills. For example Trainable would refer to the
                    skills you can train,
                    Crafting would refer to any skill that you can level through crafting.
                </p>
            @else
                <dl>
                    <dt>Effects Skill</dt>
                    <dd>{{ !is_null($item->skill_name) ? $item->skill_name : 'N/A' }}</dd>
                    <dt>Skill Bonus</dt>
                    <dd>{{ $item->skill_bonus * 100 }}%</dd>
                    <dt>Skill XP Bonus</dt>
                    <dd>{{ $item->skill_training_bonus * 100 }}%</dd>
                </dl>
            @endif
        </div>
    </div>

</x-core.cards.card-with-title>

@if ($item->can_craft)
    <x-core.cards.card css="mb-4">
        <div class="grid md:grid-cols-2 gap-3">
            <div>
                <strong>Crafting Information</strong>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
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
            <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <div>
                <strong>Crafting Cost</strong>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <dl>
                    @if (!is_null($item->gold_cost) || $item->gold_cost > 0)
                        <dt>Gold Cost</dt>
                        <dd>{{ number_format($item->gold_cost) }}</dd>
                    @endif

                    @if (!is_null($item->gold_dust_cost) || $item->gold_dust_cost > 0)
                        <dt>Gold Dust Cost</dt>
                        <dd>{{ number_format($item->gold_dust_cost) }}</dd>
                    @endif

                    @if (!is_null($item->shards_cost) || $item->shards_cost > 0)
                        <dt>Gold Cost</dt>
                        <dd>{{ number_format($item->shards_cost) }}</dd>
                    @endif

                    @if (!is_null($item->copper_coin_cost) || $item->copper_coin_cost > 0)
                        <dt>Gold Cost</dt>
                        <dd>{{ number_format($item->copper_coin_cost) }}</dd>
                    @endif
                </dl>
            </div>
        </div>
    </x-core.cards.card>
@endif
