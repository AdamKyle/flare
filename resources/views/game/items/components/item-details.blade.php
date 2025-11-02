<div
  @if ($item->type === 'quest') class="grid md:grid-cols-2 gap-4" @else class="" @endif
>
  <div>
    <x-core.cards.card-with-title title="Details" buttons="false">
      <p class="mb-4 text-sky-600 dark:text-sky-400 italic">
        {{ nl2br($item->description) }}
      </p>
      <div class="grid gap-3 md:grid-cols-3">
        <div>
          <strong>Stats</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Str Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->str_mod * 100 }}%</x-core.dl.dd>
            <x-core.dl.dt>Dex Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->dex_mod * 100 }}%</x-core.dl.dd>
            <x-core.dl.dt>Agi Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->agi_mod * 100 }}%</x-core.dl.dd>
            <x-core.dl.dt>Chr Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->chr_mod * 100 }}%</x-core.dl.dd>
            <x-core.dl.dt>Dur Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->dur_mod * 100 }}%</x-core.dl.dd>
            <x-core.dl.dt>Int Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->int_mod * 100 }}%</x-core.dl.dd>
            <x-core.dl.dt>Focus Modifier</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->focus_mod * 100 }}%</x-core.dl.dd>
          </x-core.dl.dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <strong>Modifiers</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Base Damage</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->base_damage > 0 ? $item->base_damage : 0 }}</x-core.dl.dd>
            <x-core.dl.dt>Base Ac</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->base_ac > 0 ? $item->base_ac : 0 }}</x-core.dl.dd>
            <x-core.dl.dt>Base Healing</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->base_healing > 0 ? $item->base_healing : 0 }}</x-core.dl.dd>
            <x-core.dl.dt>Base Damage Mod</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->base_damage_mod * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Base Ac Mod</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->base_ac_mod * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Base Healing Mod</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->base_healing_mod * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
          
          <x-core.separator.separator />
          <strong>Skill Modifiers</strong>
          <x-core.separator.separator />

          @if ($item->type === 'alchemy')
            <x-core.dl.dl>
              <x-core.dl.dt>Effects Skill(s)</x-core.dl.dt>
              <x-core.dl.dd>
                {{ ! is_null($item->affects_skill_type) ? implode(',', $skills) : 'N/A' }}
              </x-core.dl.dd>
              <x-core.dl.dt>Skill Bonus</x-core.dl.dt>
              <x-core.dl.dd>{{ $item->increase_skill_bonus_by * 100 }}%</x-core.dl.dd>
              <x-core.dl.dt>Skill XP Bonus</x-core.dl.dt>
              <x-core.dl.dd>{{ $item->increase_skill_training_bonus_by * 100 }}%</x-core.dl.dd>
            </x-core.dl.dl>
          @else
            <x-core.dl.dl>
              <x-core.dl.dt>Effects Skill</x-core.dl.dt>
              <x-core.dl.dd>
                {{ ! is_null($item->skill_name) ? $item->skill_name : 'N/A' }}
              </x-core.dl.dd>
              <x-core.dl.dt>Skill Bonus</x-core.dl.dt>
              <x-core.dl.dd>{{ $item->skill_bonus * 100 }}%</x-core.dl.dd>
              <x-core.dl.dt>Skill XP Bonus</x-core.dl.dt>
              <x-core.dl.dd>{{ $item->skill_training_bonus * 100 }}%</x-core.dl.dd>
            </x-core.dl.dl>
          @endif
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <strong>Evasion and Reductions</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Spell Evasion</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->spell_evasion * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Healing Reduction</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->healing_reduction * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Affix Dmg. Reduction</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->affix_damage_reduction * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
          <x-core.separator.separator />
          <strong>Timeout reductions</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Fight Timeout Reduction</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->fight_time_out_mod_bonus * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Move Timeout Reduction</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->move_time_out_mod_bonus * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
          <x-core.separator.separator />
          <div class="mt-4">
            <div class="mb-4">
              @if (! is_null($item->itemPrefix))
                <x-core.buttons.orange-button
                  data-target="#affix-details-{{$item->itemPrefix->id}}"
                  data-toggle="modal"
                >
                  View {{ $item->itemPrefix->name }} Prefix
                </x-core.buttons.orange-button>
              @endif
            </div>
            <div class="mb-4">
              @if (! is_null($item->itemSuffix))
                <x-core.buttons.orange-button
                  data-target="#affix-details-{{$item->itemSuffix->id}}"
                  data-toggle="modal"
                >
                  View {{ $item->itemSuffix->name }} Suffix
                </x-core.buttons.orange-button>
              @endif
            </div>
          </div>
        </div>
      </div>
    </x-core.cards.card-with-title>

    <x-core.cards.card-with-title title="Devouring and Resistances" buttons="false">
      <div class="grid gap-3 md:grid-cols-3">
        <div>
          <strong>Devouring Chance</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Devouring Light</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->devouring_light * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Devouring Darkness</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->devouring_darkness * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <strong>Resurrection</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Chance</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->resurrection_chance * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <strong>Holy Info</strong>
          <x-core.separator.separator />
          <p class="mb-4">
            Indicates how many can be applied to the item, via the
            <a href="/information/holy-items" target="_blank">
              <i class="fas fa-external-link-alt"></i>
              Purgatory Smith Work Bench.
            </a>
          </p>
          <x-core.dl.dl>
            <x-core.dl.dt>Holy Stacks</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->holy_stacks }}</x-core.dl.dd>
          </x-core.dl.dl>
        </div>
      </div>
    </x-core.cards.card-with-title>

    <x-core.cards.card-with-title title="Ambush and Counter" buttons="false">
      <div class="grid gap-3 md:grid-cols-2">
        <div>
          <strong>Ambush Info</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Chance</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->ambush_chance * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Resistance</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->ambush_resistance * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <strong>Counter</strong>
          <x-core.separator.separator />
          <x-core.dl.dl>
            <x-core.dl.dt>Chance</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->counter_chance * 100 }} %</x-core.dl.dd>
            <x-core.dl.dt>Resistance</x-core.dl.dt>
            <x-core.dl.dd>{{ $item->counter_resistance * 100 }} %</x-core.dl.dd>
          </x-core.dl.dl>
        </div>
      </div>
    </x-core.cards.card-with-title>

    @if ($item->can_craft)
      <x-core.cards.card-with-title title="Crafting Information" buttons="false">
        <div class="grid gap-3 md:grid-cols-2">
          <div>
            <strong>Crafting Information</strong>
            <div
              class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <x-core.dl.dl>
              <x-core.dl.dt>Skill Required</x-core.dl.dt>
              <x-core.dl.dd>
                @if ($item->crafting_type !== 'trinketry' || $item->crafting_type !== 'alchemy')
                  {{ ucfirst($item->crafting_type) }}
                @else
                  {{ ucfirst($item->crafting_type) }} Crafting
                @endif
              </x-core.dl.dd>
              <x-core.dl.dt>Skill Level Required</x-core.dl.dt>
              <x-core.dl.dd>{{ $item->skill_level_required }}</x-core.dl.dd>
              <x-core.dl.dt>Becomes Trivial at (no XP)</x-core.dl.dt>
              <x-core.dl.dd>{{ $item->skill_level_trivial }}</x-core.dl.dd>
            </x-core.dl.dl>
          </div>
          <div
            class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
          ></div>
          <div>
            <strong>Crafting Cost</strong>
            <div
              class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <x-core.dl.dl>
              @if ($item->cost > 0)
                <x-core.dl.dt>Gold Cost</x-core.dl.dt>
                <x-core.dl.dd>{{ number_format($item->cost) }}</x-core.dl.dd>
              @endif

              @if ($item->gold_dust_cost > 0)
                <x-core.dl.dt>Gold Dust Cost</x-core.dl.dt>
                <x-core.dl.dd>{{ number_format($item->gold_dust_cost) }}</x-core.dl.dd>
              @endif

              @if ($item->shards_cost > 0)
                <x-core.dl.dt>Shards Cost</x-core.dl.dt>
                <x-core.dl.dd>{{ number_format($item->shards_cost) }}</x-core.dl.dd>
              @endif

              @if ($item->copper_coin_cost > 0)
                <x-core.dl.dt>Copper Coin Cost</x-core.dl.dt>
                <x-core.dl.dd>{{ number_format($item->copper_coin_cost) }}</x-core.dl.dd>
              @endif
            </x-core.dl.dl>
          </div>
        </div>
      </x-core.cards.card-with-title>
    @endif

    @if (! is_null($item->itemSkill))
      <x-core.cards.card-with-title title="Item Skills">
        <p class="my-4">
          This item has skills attached to it. below is the parent skill, from
          there, you will see other child skills this item has to offer.
        </p>

        <p class="my-4">
          Items with item skills cannot be sold on the market and cannot be
          sold. They are bound to your character and there skills effects only
          take place whe equipped.
        </p>

        @livewire(
          'admin.item-skills.item-skills-table',
          [
            'itemSkillId' => $item->itemSkill->id,
          ]
        )
      </x-core.cards.card-with-title>
    @endif
  </div>
  @include('game.items.components.items-quest-details', ['item' => $item])
</div>

@if (! is_null($item->itemPrefix))
  @include('game.items.affix_details', ['itemAffix' => $item->itemPrefix])
@endif

@if (! is_null($item->itemSuffix))
  @include('game.items.affix_details', ['itemAffix' => $item->itemSuffix])
@endif
