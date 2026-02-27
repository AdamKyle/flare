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

    <x-core.cards.card-with-title title="Info">

          <h3 class="text-sky-600 dark:text-sky-500 mt-2 mb-4">Requirements</h3>
          <x-core.separator.separator />
          <x-core.dl.dl class="mb-5">
            @if (! is_null($guideQuest->required_level))
              <x-core.dl.dt>Required Player Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_level }}</x-core.dl.dd>
            @endif

<<<<<<< HEAD
            @if (! is_null($guideQuest->required_event_goal_participation))
              <x-core.dl.dt>Participate in the Event Goal and Kill # of Creatures:</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_event_goal_participation }}</x-core.dl.dd>
            @endif
=======
        <x-core.cards.card>
            <div class='grid md:grid-cols-2 gap-4'>
                <div>
                    <h3 class="text-sky-600 dark:text-sky-500">Requirements</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl class='mb-5'>
                        @if (!is_null($guideQuest->required_level))
                            <dt>Required Player Level</dt>
                            <dd>{{ $guideQuest->required_level }}</dd>
                        @endif
                          @if (!is_null($guideQuest->required_reincarnation_amount))
                            <dt>Required Player Reincarnation Amount</dt>
                            <dd>{{ $guideQuest->required_reincarnation_amount }}</dd>
                          @endif
                        @if (!is_null($guideQuest->required_event_goal_participation))
                            <dt>Participate in the Event Goal and Kill # of Creatures:</dt>
                            <dd>{{$guideQuest->required_event_goal_participation}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_game_map_id))
                            <dt>Required Access To Map</dt>
                            <dd>{{ $guideQuest->game_map_name }}</dd>
                        @endif
                        @if (!is_null($guideQuest->skill_name))
                            <dt>Required Skill</dt>
                            <dd>{{ $guideQuest->skill_name }}</dd>
                            <dt>Required Skill Level</dt>
                            <dd>{{ $guideQuest->required_skill_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->secondary_skill_name))
                            <dt>Required Secondary Skill</dt>
                            <dd>{{ $guideQuest->secondary_skill_name }}</dd>
                            <dt>Required Secondary Skill Level</dt>
                            <dd>{{ $guideQuest->required_secondary_skill_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->skill_type_name))
                            <dt>Requireed Skill Type</dt>
                            <dd>{{ $guideQuest->skill_type_name }}</dd>
                            <dt>Required Skill Type Level</dt>
                            <dd>{{ $guideQuest->required_skill_type_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->faction_name))
                            <dt>Required Faction</dt>
                            <dd>{{ $guideQuest->faction_name }}</dd>
                            <dt>Required Faction Level</dt>
                            <dd>{{ $guideQuest->required_faction_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->be_on_game_map))
                            <dt>Physically be on Map:</dt>
                            <dd>{{$guideQuest->required_to_be_on_game_map_name}}</dd>
                        @endif
                        @if (!is_null($guideQuest->quest_name))
                            <dt>Required Quest</dt>
                            <dd>{{ $guideQuest->quest_name }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_quest_item_id))
                            <dt>Required Quest Item</dt>
                            <dd>{{ $guideQuest->quest_item_name }}</dd>
                        @endif
                        @if (!is_null($guideQuest->secondary_quest_item_id))
                            <dt>Secondary Required Quest Item</dt>
                            <dd>{{ $guideQuest->secondary_quest_item_name }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdoms))
                            <dt>Required Kingdom Amount</dt>
                            <dd>{{ $guideQuest->required_kingdoms }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdom_level))
                            <dt>Required Kingdom Building Level (combined)</dt>
                            <dd>{{ $guideQuest->required_kingdom_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdom_building_id))
                            <dt>Required Kingdom Building: {{ $guideQuest->kingdom_building_name }} to level</dt>
                            <dd>{{ $guideQuest->required_kingdom_building_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_kingdom_units))
                            <dt>Required Kingdom Units (combined)</dt>
                            <dd>{{ $guideQuest->required_kingdom_units }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_passive_skill))
                            <dt>Required Passive Name</dt>
                            <dd>{{ $guideQuest->passive_name }}</dd>
                            <dt>Required Passive Level</dt>
                            <dd>{{ $guideQuest->required_passive_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_class_specials_equipped))
                            <dt>Required Class Specials Equipped</dt>
                            <dd>{{ $guideQuest->required_class_specials_equipped }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_class_rank_level))
                            <dt>Required Current Class Rank Level</dt>
                            <dd>{{ $guideQuest->required_class_rank_level }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_stats))
                            <dt>Required Stats</dt>
                            <dd>{{ number_format($guideQuest->required_stats) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_str))
                            <dt>Required Strengh</dt>
                            <dd>{{ number_format($guideQuest->required_str) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_dex))
                            <dt>Required Dexterity</dt>
                            <dd>{{ number_format($guideQuest->required_dex) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_dur))
                            <dt>Required Dexterity</dt>
                            <dd>{{ number_format($guideQuest->required_dur) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_agi))
                            <dt>Required Agility</dt>
                            <dd>{{ number_format($guideQuest->required_agi) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_int))
                            <dt>Required Intelligence</dt>
                            <dd>{{ number_format($guideQuest->required_int) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_chr))
                            <dt>Required Charisma</dt>
                            <dd>{{ number_format($guideQuest->required_chr) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_focus))
                            <dt>Required Focus</dt>
                            <dd>{{ number_format($guideQuest->required_focus) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_specialty_type))
                            <dt>Required Set Item Type</dt>
                            <dd>{{$guideQuest->required_specialty_type}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_holy_stacks))
                            <dt>Required Holy Stacks</dt>
                            <dd>{{$guideQuest->required_holy_stacks}}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_gold))
                            <dt>Required Gold</dt>
                            <dd>{{ number_format($guideQuest->required_gold) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_gold_dust))
                            <dt>Required Gold Dust</dt>
                            <dd>{{ number_format($guideQuest->required_gold_dust) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_shards))
                            <dt>Required Shards</dt>
                            <dd>{{ number_format($guideQuest->required_shards) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_copper_coins))
                            <dt>Required Copper Coins</dt>
                            <dd>{{ number_format($guideQuest->required_copper_coins) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_gold_bars))
                            <dt>Required Gold Bars</dt>
                            <dd>{{ number_format($guideQuest->required_gold_bars) }}</dd>
                        @endif
                        @if (!is_null($guideQuest->required_fame_level))
                            <dt>Required Fame Level</dt>
                            <dd>{{ number_format($guideQuest->required_fame_level) }}</dd>
                        @endif
                          @if (!is_null($guideQuest->required_delve_survival_time))
                            <dt>Required Delve Survival Hour(s)</dt>
                            <dd>{{ number_format($guideQuest->required_delve_survival_time) }}</dd>
                          @endif
                          @if (!is_null($guideQuest->required_delve_pack_size))
                            <dt>Required Delve Pack Size</dt>
                            <dd>{{ number_format($guideQuest->required_delve_pack_size) }}</dd>
                          @endif
                    </dl>
                    <h3 class="text-sky-600 dark:text-sky-500">XP Reward</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl class='my-4'>
                        <dt>XP Reward</dt>
                        <dd>{{ is_null($guideQuest->xp_reward) ? 0 : number_format($guideQuest->xp_reward) }}</dd>
                    </dl>
                    <h3 class="text-sky-600 dark:text-sky-500">Currency Rewards</h3>
                    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                    <dl>
                        <dt>Gold Reward</dt>
                        <dd>{{ is_null($guideQuest->gold_reward) ? 0 : number_format($guideQuest->gold_reward) }}</dd>
                        <dt>Gold Dust Reward</dt>
                        <dd>{{ is_null($guideQuest->gold_dust_reward) ? 0 : number_format($guideQuest->gold_dust_reward) }}
                        </dd>
                        <dt>Shards Reward</dt>
                        <dd>{{ is_null($guideQuest->shards_reward) ? 0 : number_format($guideQuest->shards_reward) }}</dd>
                    </dl>
                    @if (!is_null($guideQuest->eventType()) || !is_null($guideQuest->unlock_at_level))
                        <div class="my-4">
                            <x-core.alerts.info-alert title="Event Specific Guide Quest!">
                                <p>This Guide quest is only available when it unlocks at a specific level, is used for an event or both.</p>
                                <p class="my-2">These types of quests interrupt what the player was doing to introduce them to new features.</p>
                                <div class='border-b-2 border-b-blue-300 dark:border-b-blue-600 my-3'></div>
                                <dl>
                                    <dt>Unlocks at Level</dt>
                                    <dd>{{ $guideQuest->unlock_at_level }}</dd>
                                    @if (!is_null($guideQuest->eventType()))
                                        <dt>Only During Event:</dt>
                                        <dd>{{ $guideQuest->eventType()->getNameForEvent() }}</dd>
                                    @endif
                                    <dt>Parent ID</dt>
                                    <dd>{{ is_null($guideQuest->parent_id) ? 'N/A' : $guideQuest->parent_quest_name }}</dd>
                                </dl>
                            </x-core.alerts.info-alert>
                        </div>
                    @endif
                </div>
                <div class='block md:hidden border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div>
                    <div class="border-1 rounded-sm p-2 bg-slate-300 dark:bg-slate-700 max-h-[250px] overflow-x-auto mb-4">
                        <h3 class="mb-4">Intro Text</h3>
                        <div>
                            {!! nl2br($guideQuest->intro_text) !!}
                        </div>
                    </div>
>>>>>>> master

            @if (! is_null($guideQuest->required_game_map_id))
              <x-core.dl.dt>Required Access To Map</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->game_map_name }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->skill_name))
              <x-core.dl.dt>Required Skill</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->skill_name }}</x-core.dl.dd>
              <x-core.dl.dt>Required Skill Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_skill_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->secondary_skill_name))
              <x-core.dl.dt>Required Secondary Skill</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->secondary_skill_name }}</x-core.dl.dd>
              <x-core.dl.dt>Required Secondary Skill Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_secondary_skill_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->skill_type_name))
              <x-core.dl.dt>Requireed Skill Type</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->skill_type_name }}</x-core.dl.dd>
              <x-core.dl.dt>Required Skill Type Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_skill_type_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->faction_name))
              <x-core.dl.dt>Required Faction</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->faction_name }}</x-core.dl.dd>
              <x-core.dl.dt>Required Faction Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_faction_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->be_on_game_map))
              <x-core.dl.dt>Physically be on Map:</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_to_be_on_game_map_name }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->quest_name))
              <x-core.dl.dt>Required Quest</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->quest_name }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_quest_item_id))
              <x-core.dl.dt>Required Quest Item</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->quest_item_name }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->secondary_quest_item_id))
              <x-core.dl.dt>Secondary Required Quest Item</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->secondary_quest_item_name }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_kingdoms))
              <x-core.dl.dt>Required Kingdom Amount</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_kingdoms }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_kingdom_level))
              <x-core.dl.dt>Required Kingdom Building Level (combined)</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_kingdom_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_kingdom_building_id))
              <x-core.dl.dt>Required Kingdom Building: {{ $guideQuest->kingdom_building_name }} to level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_kingdom_building_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_kingdom_units))
              <x-core.dl.dt>Required Kingdom Units (combined)</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_kingdom_units }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_passive_skill))
              <x-core.dl.dt>Required Passive Name</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->passive_name }}</x-core.dl.dd>
              <x-core.dl.dt>Required Passive Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_passive_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_class_specials_equipped))
              <x-core.dl.dt>Required Class Specials Equipped</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_class_specials_equipped }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_class_rank_level))
              <x-core.dl.dt>Required Current Class Rank Level</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_class_rank_level }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_stats))
              <x-core.dl.dt>Required Stats</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_stats) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_str))
              <x-core.dl.dt>Required Strengh</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_str) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_dex))
              <x-core.dl.dt>Required Dexterity</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_dex) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_dur))
              <x-core.dl.dt>Required Dexterity</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_dur) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_agi))
              <x-core.dl.dt>Required Agility</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_agi) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_int))
              <x-core.dl.dt>Required Intelligence</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_int) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_chr))
              <x-core.dl.dt>Required Charisma</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_chr) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_focus))
              <x-core.dl.dt>Required Focus</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_focus) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_specialty_type))
              <x-core.dl.dt>Required Set Item Type</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_specialty_type }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_holy_stacks))
              <x-core.dl.dt>Required Holy Stacks</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_holy_stacks }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_gold))
              <x-core.dl.dt>Required Gold</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_gold) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_gold_dust))
              <x-core.dl.dt>Required Gold Dust</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_gold_dust) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_shards))
              <x-core.dl.dt>Required Shards</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_shards) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_copper_coins))
              <x-core.dl.dt>Required Copper Coins</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_copper_coins) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_gold_bars))
              <x-core.dl.dt>Required Gold Bars</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_gold_bars) }}</x-core.dl.dd>
            @endif

            @if (! is_null($guideQuest->required_fame_level))
              <x-core.dl.dt>Required Fame Level</x-core.dl.dt>
              <x-core.dl.dd>{{ number_format($guideQuest->required_fame_level) }}</x-core.dl.dd>
            @endif
          </x-core.dl.dl>

          <h3 class="text-sky-600 dark:text-sky-500 mt-2 mb-4">Rewards</h3>
          <x-core.separator.separator />
          <x-core.dl.dl class="my-4">
            <x-core.dl.dt>XP Reward</x-core.dl.dt>
            <x-core.dl.dd>{{ is_null($guideQuest->xp_reward) ? 0 : number_format($guideQuest->xp_reward) }}</x-core.dl.dd>
            <x-core.dl.dt>Gold Reward</x-core.dl.dt>
            <x-core.dl.dd>{{ is_null($guideQuest->gold_reward) ? 0 : number_format($guideQuest->gold_reward) }}</x-core.dl.dd>
            <x-core.dl.dt>Gold Dust Reward</x-core.dl.dt>
            <x-core.dl.dd>{{ is_null($guideQuest->gold_dust_reward) ? 0 : number_format($guideQuest->gold_dust_reward) }}</x-core.dl.dd>
            <x-core.dl.dt>Shards Reward</x-core.dl.dt>
            <x-core.dl.dd>{{ is_null($guideQuest->shards_reward) ? 0 : number_format($guideQuest->shards_reward) }}</x-core.dl.dd>
          </x-core.dl.dl>

          @if (! is_null($guideQuest->eventType()) || ! is_null($guideQuest->unlock_at_level))
            <div class="my-4">
              <x-core.alerts.info-alert title="Event Specific Guide Quest!">
                <p>This Guide quest is only available when it unlocks at a specific level, is used for an event or both.</p>
                <p class="my-2">These types of quests interrupt what the player was doing to introduce them to new features.</p>
                <div class="my-3 border-b-2 border-b-blue-300 dark:border-b-blue-600"></div>
                <x-core.dl.dl>
                  <x-core.dl.dt>Unlocks at Level</x-core.dl.dt>
                  <x-core.dl.dd>{{ $guideQuest->unlock_at_level }}</x-core.dl.dd>
                  @if (! is_null($guideQuest->eventType()))
                    <x-core.dl.dt>Only During Event:</x-core.dl.dt>
                    <x-core.dl.dd>{{ $guideQuest->eventType()->getNameForEvent() }}</x-core.dl.dd>
                  @endif
                  <x-core.dl.dt>Parent ID</x-core.dl.dt>
                  <x-core.dl.dd>{{ is_null($guideQuest->parent_id) ? 'N/A' : $guideQuest->parent_quest_name }}</x-core.dl.dd>
                </x-core.dl.dl>
              </x-core.alerts.info-alert>
            </div>
          @endif
    </x-core.cards.card-with-title>

    <x-core.cards.card-with-title title="Intro">
      <div class="space-y-6">
        @php
          $introBlocks = is_array($guideQuest->intro_text ?? null) ? $guideQuest->intro_text : [];
        @endphp

        @forelse ($introBlocks as $block)

            @if (!empty($block['image_url']))
            <div class="grid gap-8 md:grid-cols-5 items-start">
              <div class="md:col-span-2">
                <div class="aspect-video overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700 bg-gray-100 dark:bg-gray-800">
                  <img src="{{ $block['image_url'] }}" alt="" class="h-full w-full object-cover">
                </div>
              </div>
              <div class="md:col-span-3">
                <div class="prose dark:prose-invert max-w-none text-left">
                  @convertMarkdownToHtml($block['content'])
                </div>
              </div>
            </div>
            @else
            <div class="mx-auto w-full md:w-2/3">
              <div class="prose dark:prose-invert max-w-none text-left">
                @convertMarkdownToHtml($block['content'])
              </div>
            </div>
            @endif
        @empty
          <div class="text-center text-sm text-gray-600 dark:text-gray-400">No content yet.</div>
        @endforelse
      </div>
    </x-core.cards.card-with-title>


    <x-core.cards.card-with-title title="Desktop Instructions">
      <div class="space-y-6">
        @php
          $desktopInstructions = is_array($guideQuest->desktop_instructions ?? null) ? $guideQuest->desktop_instructions : [];
        @endphp

        @forelse ($desktopInstructions as $block)

          @if (!empty($block['image_url']))
            <div class="grid gap-8md:grid-cols-5 items-start">
              <div class="md:col-span-2">
                <div class="aspect-video overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700 bg-gray-100 dark:bg-gray-800">
                  <img src="{{ $block['image_url'] }}" alt="" class="h-full w-full object-cover">
                </div>
              </div>
              <div class="md:col-span-3">
                <div class="prose dark:prose-invert max-w-none text-left">
                  @convertMarkdownToHtml($block['content'])
                </div>
              </div>
            </div>
          @else
            <div class="mx-auto w-full md:w-2/3">
              <div class="prose dark:prose-invert max-w-none text-left">
                @convertMarkdownToHtml($block['content'])
              </div>
            </div>
          @endif
        @empty
          <div class="text-center text-sm text-gray-600 dark:text-gray-400">No content yet.</div>
        @endforelse
      </div>
    </x-core.cards.card-with-title>

    <x-core.cards.card-with-title title="Mobile Instructions">
      <div class="space-y-6">
        @php
          $mobileInstructions = is_array($guideQuest->mobile_instructions ?? null) ? $guideQuest->mobile_instructions : [];
        @endphp

        @forelse ($mobileInstructions as $block)

          @if (!empty($block['image_url']))
            <div class="grid gap-4 md:grid-cols-5 items-start">
              <div class="md:col-span-2">
                <div class="aspect-video overflow-hidden rounded-md ring-1 ring-gray-300 dark:ring-gray-700 bg-gray-100 dark:bg-gray-800">
                  <img src="{{ $block['image_url'] }}" alt="" class="h-full w-full object-cover">
                </div>
              </div>
              <div class="md:col-span-3">
                <div class="prose dark:prose-invert max-w-none text-left">
                  @convertMarkdownToHtml($block['content'])
                </div>
              </div>
            </div>
          @else
            <div class="mx-auto w-full md:w-2/3">
              <div class="prose dark:prose-invert max-w-none text-left">
                @convertMarkdownToHtml($block['content'])
              </div>
            </div>
          @endif
        @empty
          <div class="text-center text-sm text-gray-600 dark:text-gray-400">No content yet.</div>
        @endforelse
      </div>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
