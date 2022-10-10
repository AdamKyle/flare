<div @if ($item->type === 'quest') class="grid md:grid-cols-2 gap-4" @else class="" @endif>
    <div>
        <x-core.cards.card-with-title
            title="Details"
            buttons="false"
        >
            <p class="mt-4 mb-4 text-sky-600 dark:text-sky-400">
                {{nl2br($item->description)}}
            </p>
            <div class="grid md:grid-cols-3 gap-3">
                <div>
                    <strong>Stats</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Str Modifier</dt>
                        <dd>{{$item->str_mod * 100}}%</dd>
                        <dt>Dex Modifier</dt>
                        <dd>{{$item->dex_mod * 100}}%</dd>
                        <dt>Agi Modifier</dt>
                        <dd>{{$item->agi_mod * 100}}%</dd>
                        <dt>Chr Modifier</dt>
                        <dd>{{$item->chr_mod * 100}}%</dd>
                        <dt>Dur Modifier</dt>
                        <dd>{{$item->dur_mod * 100}}%</dd>
                        <dt>Int Modifier</dt>
                        <dd>{{$item->int_mod * 100}}%</dd>
                        <dt>Focus Modifier</dt>
                        <dd>{{$item->focus_mod * 100}}%</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <strong>Modifiers</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Base Damage</dt>
                        <dd>{{$item->base_damage > 0 ? $item->base_damage : 0}}</dd>
                        <dt>Base Ac</dt>
                        <dd>{{$item->base_ac > 0 ? $item->base_ac : 0}}</dd>
                        <dt>Base Healing</dt>
                        <dd>{{$item->base_healing > 0 ? $item->base_healing : 0}}</dd>
                        <dt>Base Damage Mod</dt>
                        <dd>{{$item->base_damage_mod * 100}} %</dd>
                        <dt>Base Ac Mod</dt>
                        <dd>{{$item->base_ac_mod * 100}} %</dd>
                        <dt>Base Healing Mod</dt>
                        <dd>{{$item->base_healing_mod * 100}} %</dd>
                    </dl>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <strong>Skill Modifiers</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Effects Skill</dt>
                        <dd>{{!is_null($item->skill_name) ? $item->skill_name : 'N/A'}}</dd>
                        <dt>Skill Bonus</dt>
                        <dd>{{$item->skill_bonus * 100}}%</dd>
                        <dt>Skill XP Bonus</dt>
                        <dd>{{$item->skill_training_bonus * 100}}%</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <strong>Evasion and Reductions</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Spell Evasion</dt>
                        <dd>{{$item->spell_evasion * 100}} %</dd>
                        <dt>Healing Reduction</dt>
                        <dd>{{$item->healing_reduction * 100}} %</dd>
                        <dt>Affix Dmg. Reduction</dt>
                        <dd>{{$item->affix_damage_reduction * 100}} %</dd>
                    </dl>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div class="mt-4">
                        <div class="mb-4">
                            @if (!is_null($item->itemPrefix))
                                <x-core.buttons.orange-button data-target="#affix-details-{{$item->itemPrefix->id}}" data-toggle="modal">
                                    View {{$item->itemPrefix->name}} Prefix
                                </x-core.buttons.orange-button>
                            @endif
                        </div>
                        <div class="mb-4">
                            @if (!is_null($item->itemSuffix))
                                <x-core.buttons.orange-button data-target="#affix-details-{{$item->itemSuffix->id}}" data-toggle="modal">
                                    View {{$item->itemSuffix->name}} Suffix
                                </x-core.buttons.orange-button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-core.cards.card-with-title>

        <x-core.cards.card css="mt-4 mb-4">
            <div class="grid md:grid-cols-3 gap-3">
                <div>
                    <strong>Devouring Chance</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Devouring Light</dt>
                        <dd>{{$item->devouring_light * 100}} %</dd>
                        <dt>Devouring Darkness</dt>
                        <dd>{{$item->devouring_darkness * 100}} %</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <strong>Resurrection</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Chance</dt>
                        <dd>{{$item->resurrection_chance * 100}} %</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <strong>Holy Info</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <p class="mb-4">Indicates how many can be applied to the item, via the <a href="/information/holy-items" target="_blank"><i class="fas fa-external-link-alt"></i>Purgatory Smith Work Bench.</a></p>
                    <dl>
                        <dt>Holy Stacks</dt>
                        <dd>{{$item->holy_stacks}}</dd>
                    </dl>
                </div>
            </div>
        </x-core.cards.card>

        <x-core.cards.card css="mb-4">
            <div class="grid md:grid-cols-2 gap-3">
                <div>
                    <strong>Ambush Info</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Chance</dt>
                        <dd>{{$item->ambush_chance * 100}} %</dd>
                        <dt>Resistance</dt>
                        <dd>{{$item->ambush_resistance * 100}} %</dd>
                    </dl>
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <strong>Counter</strong>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Chance</dt>
                        <dd>{{$item->counter_chance * 100}} %</dd>
                        <dt>Resistance</dt>
                        <dd>{{$item->counter_resistance * 100}} %</dd>
                    </dl>
                </div>
            </div>
        </x-core.cards.card>

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
                                    {{ucfirst($item->crafting_type)}}
                                @else
                                    {{ucfirst($item->crafting_type)}} Crafting
                                @endif
                            </dd>
                            <dt>Skill Level Required</dt>
                            <dd>{{$item->skill_level_required}}</dd>
                            <dt>Becomes Trivial at (no XP)</dt>
                            <dd>{{$item->skill_level_trivial}}</dd>
                        </dl>
                    </div>
                    <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <div>
                        <strong>Crafting Cost</strong>
                        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                        <dl>
                            @if (!is_null($item->gold_cost) || $item->gold_cost > 0)
                                <dt>Gold Cost</dt>
                                <dd>{{number_format($item->gold_cost)}}</dd>
                            @endif

                            @if (!is_null($item->gold_dust_cost) || $item->gold_dust_cost > 0)
                                <dt>Gold Dust Cost</dt>
                                <dd>{{number_format($item->gold_dust_cost)}}</dd>
                            @endif

                            @if (!is_null($item->shards_cost) || $item->shards_cost > 0)
                                <dt>Gold Cost</dt>
                                <dd>{{number_format($item->shards_cost)}}</dd>
                            @endif

                            @if (!is_null($item->copper_coin_cost) || $item->copper_coin_cost > 0)
                                <dt>Gold Cost</dt>
                                <dd>{{number_format($item->copper_coin_cost)}}</dd>
                            @endif
                        </dl>
                    </div>
                </div>
            </x-core.cards.card>
        @endif
    </div>
    @include('game.items.components.items-quest-details', ['item' => $item])
</div>

@if (!is_null($item->itemPrefix))
    @include('game.items.affix_details', ['itemAffix' => $item->itemPrefix])
@endif

@if (!is_null($item->itemSuffix))
    @include('game.items.affix_details', ['itemAffix' => $item->itemSuffix])
@endif

