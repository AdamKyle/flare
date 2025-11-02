@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    <x-core.page.title
      title="{{ $guideQuest->name }}"
      route="{{ auth()->user()->hasRole('Admin')? route('admin.guide-quests'): route('completed.guide-quests', ['user' => auth()->user()->id]) }}"
      color="success"
      link="{{ auth()->user()->hasRole('Admin')? 'Guide Quests': 'Back' }}"
    >
      @if (auth()->user()->hasRole('Admin'))
        <x-core.buttons.link-buttons.primary-button
          href="{{ route('admin.guide-quests.edit', ['guideQuest' => $guideQuest->id]) }}"
        >
          Edit Quest
        </x-core.buttons.link-buttons.primary-button>
      @endif
    </x-core.page.title>

    <x-core.cards.card>
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">Requirements</h3>
          <x-core.separator.separator />
          <dl class="mb-5">
            @if (! is_null($guideQuest->required_level))
              <dt>Required Player Level</dt>
              <dd>{{ $guideQuest->required_level }}</dd>
            @endif

            @if (! is_null($guideQuest->required_event_goal_participation))
              <dt>Participate in the Event Goal and Kill # of Creatures:</dt>
              <dd>
                {{ $guideQuest->required_event_goal_participation }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_game_map_id))
              <dt>Required Access To Map</dt>
              <dd>{{ $guideQuest->game_map_name }}</dd>
            @endif

            @if (! is_null($guideQuest->skill_name))
              <dt>Required Skill</dt>
              <dd>{{ $guideQuest->skill_name }}</dd>
              <dt>Required Skill Level</dt>
              <dd>{{ $guideQuest->required_skill_level }}</dd>
            @endif

            @if (! is_null($guideQuest->secondary_skill_name))
              <dt>Required Secondary Skill</dt>
              <dd>{{ $guideQuest->secondary_skill_name }}</dd>
              <dt>Required Secondary Skill Level</dt>
              <dd>
                {{ $guideQuest->required_secondary_skill_level }}
              </dd>
            @endif

            @if (! is_null($guideQuest->skill_type_name))
              <dt>Requireed Skill Type</dt>
              <dd>{{ $guideQuest->skill_type_name }}</dd>
              <dt>Required Skill Type Level</dt>
              <dd>
                {{ $guideQuest->required_skill_type_level }}
              </dd>
            @endif

            @if (! is_null($guideQuest->faction_name))
              <dt>Required Faction</dt>
              <dd>{{ $guideQuest->faction_name }}</dd>
              <dt>Required Faction Level</dt>
              <dd>{{ $guideQuest->required_faction_level }}</dd>
            @endif

            @if (! is_null($guideQuest->be_on_game_map))
              <dt>Physically be on Map:</dt>
              <dd>
                {{ $guideQuest->required_to_be_on_game_map_name }}
              </dd>
            @endif

            @if (! is_null($guideQuest->quest_name))
              <dt>Required Quest</dt>
              <dd>{{ $guideQuest->quest_name }}</dd>
            @endif

            @if (! is_null($guideQuest->required_quest_item_id))
              <dt>Required Quest Item</dt>
              <dd>{{ $guideQuest->quest_item_name }}</dd>
            @endif

            @if (! is_null($guideQuest->secondary_quest_item_id))
              <dt>Secondary Required Quest Item</dt>
              <dd>
                {{ $guideQuest->secondary_quest_item_name }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_kingdoms))
              <dt>Required Kingdom Amount</dt>
              <dd>{{ $guideQuest->required_kingdoms }}</dd>
            @endif

            @if (! is_null($guideQuest->required_kingdom_level))
              <dt>Required Kingdom Building Level (combined)</dt>
              <dd>{{ $guideQuest->required_kingdom_level }}</dd>
            @endif

            @if (! is_null($guideQuest->required_kingdom_building_id))
              <dt>
                Required Kingdom Building:
                {{ $guideQuest->kingdom_building_name }} to level
              </dt>
              <dd>
                {{ $guideQuest->required_kingdom_building_level }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_kingdom_units))
              <dt>Required Kingdom Units (combined)</dt>
              <dd>{{ $guideQuest->required_kingdom_units }}</dd>
            @endif

            @if (! is_null($guideQuest->required_passive_skill))
              <dt>Required Passive Name</dt>
              <dd>{{ $guideQuest->passive_name }}</dd>
              <dt>Required Passive Level</dt>
              <dd>{{ $guideQuest->required_passive_level }}</dd>
            @endif

            @if (! is_null($guideQuest->required_class_specials_equipped))
              <dt>Required Class Specials Equipped</dt>
              <dd>
                {{ $guideQuest->required_class_specials_equipped }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_class_rank_level))
              <dt>Required Current Class Rank Level</dt>
              <dd>
                {{ $guideQuest->required_class_rank_level }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_stats))
              <dt>Required Stats</dt>
              <dd>
                {{ number_format($guideQuest->required_stats) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_str))
              <dt>Required Strengh</dt>
              <dd>
                {{ number_format($guideQuest->required_str) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_dex))
              <dt>Required Dexterity</dt>
              <dd>
                {{ number_format($guideQuest->required_dex) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_dur))
              <dt>Required Dexterity</dt>
              <dd>
                {{ number_format($guideQuest->required_dur) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_agi))
              <dt>Required Agility</dt>
              <dd>
                {{ number_format($guideQuest->required_agi) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_int))
              <dt>Required Intelligence</dt>
              <dd>
                {{ number_format($guideQuest->required_int) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_chr))
              <dt>Required Charisma</dt>
              <dd>
                {{ number_format($guideQuest->required_chr) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_focus))
              <dt>Required Focus</dt>
              <dd>
                {{ number_format($guideQuest->required_focus) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_specialty_type))
              <dt>Required Set Item Type</dt>
              <dd>{{ $guideQuest->required_specialty_type }}</dd>
            @endif

            @if (! is_null($guideQuest->required_holy_stacks))
              <dt>Required Holy Stacks</dt>
              <dd>{{ $guideQuest->required_holy_stacks }}</dd>
            @endif

            @if (! is_null($guideQuest->required_gold))
              <dt>Required Gold</dt>
              <dd>
                {{ number_format($guideQuest->required_gold) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_gold_dust))
              <dt>Required Gold Dust</dt>
              <dd>
                {{ number_format($guideQuest->required_gold_dust) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_shards))
              <dt>Required Shards</dt>
              <dd>
                {{ number_format($guideQuest->required_shards) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_copper_coins))
              <dt>Required Copper Coins</dt>
              <dd>
                {{ number_format($guideQuest->required_copper_coins) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_gold_bars))
              <dt>Required Gold Bars</dt>
              <dd>
                {{ number_format($guideQuest->required_gold_bars) }}
              </dd>
            @endif

            @if (! is_null($guideQuest->required_fame_level))
              <dt>Required Fame Level</dt>
              <dd>
                {{ number_format($guideQuest->required_fame_level) }}
              </dd>
            @endif
          </dl>
          <h3 class="text-sky-600 dark:text-sky-500">XP Reward</h3>
          <x-core.separator.separator />
          <dl class="my-4">
            <dt>XP Reward</dt>
            <dd>
              {{ is_null($guideQuest->xp_reward) ? 0 : number_format($guideQuest->xp_reward) }}
            </dd>
          </dl>
          <h3 class="text-sky-600 dark:text-sky-500">Currency Rewards</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Gold Reward</dt>
            <dd>
              {{ is_null($guideQuest->gold_reward) ? 0 : number_format($guideQuest->gold_reward) }}
            </dd>
            <dt>Gold Dust Reward</dt>
            <dd>
              {{ is_null($guideQuest->gold_dust_reward) ? 0 : number_format($guideQuest->gold_dust_reward) }}
            </dd>
            <dt>Shards Reward</dt>
            <dd>
              {{ is_null($guideQuest->shards_reward) ? 0 : number_format($guideQuest->shards_reward) }}
            </dd>
          </dl>
          @if (! is_null($guideQuest->eventType()) || ! is_null($guideQuest->unlock_at_level))
            <div class="my-4">
              <x-core.alerts.info-alert title="Event Specific Guide Quest!">
                <p>
                  This Guide quest is only available when it unlocks at a
                  specific level, is used for an event or both.
                </p>
                <p class="my-2">
                  These types of quests interrupt what the player was doing to
                  introduce them to new features.
                </p>
                <div
                  class="my-3 border-b-2 border-b-blue-300 dark:border-b-blue-600"
                ></div>
                <dl>
                  <dt>Unlocks at Level</dt>
                  <dd>{{ $guideQuest->unlock_at_level }}</dd>
                  @if (! is_null($guideQuest->eventType()))
                    <dt>Only During Event:</dt>
                    <dd>
                      {{ $guideQuest->eventType()->getNameForEvent() }}
                    </dd>
                  @endif

                  <dt>Parent ID</dt>
                  <dd>
                    {{ is_null($guideQuest->parent_id) ? 'N/A' : $guideQuest->parent_quest_name }}
                  </dd>
                </dl>
              </x-core.alerts.info-alert>
            </div>
          @endif
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <div
            class="mb-4 max-h-[250px] overflow-x-auto rounded-sm border-1 bg-slate-300 p-2 dark:bg-slate-700"
          >
            <h3 class="mb-4">Intro Text</h3>
            <div>
              {!! nl2br($guideQuest->intro_text) !!}
            </div>
          </div>

          <div
            class="max-h-[250px] overflow-x-auto rounded-sm border-1 bg-slate-300 p-2 dark:bg-slate-700"
          >
            <h3 class="mb-4">Instructions</h3>
            <div class="guide-quest-instructions">
              {!! $guideQuest->instructions !!}
            </div>
          </div>
        </div>
      </div>
    </x-core.cards.card>
    <h2 class="mt-4">Platform Instructions</h2>
    <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>
    <x-core.cards.card>
      <div class="grid grid-cols-2 gap-4">
        <div
          class="mb-4 max-h-[250px] min-h-[250px] overflow-x-auto rounded-sm border-1 bg-slate-300 p-2 dark:bg-slate-700"
        >
          <h3 class="mb-4">Desktop Instructions</h3>
          <div class="guide-quest-instructions">
            {!! $guideQuest->desktop_instructions !!}
          </div>
        </div>

        <div
          class="max-h-[250px] min-h-[250px] overflow-x-auto rounded-sm border-1 bg-slate-300 p-2 dark:bg-slate-700"
        >
          <h3 class="mb-4">Mobile Instructuions</h3>
          <div class="guide-quest-instructions">
            {!! $guideQuest->mobile_instructions !!}
          </div>
        </div>
      </div>
    </x-core.cards.card>
  </x-core.layout.info-container>
@endsection
