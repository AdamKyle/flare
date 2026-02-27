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

            @if (! is_null($guideQuest->required_event_goal_participation))
              <x-core.dl.dt>Participate in the Event Goal and Kill # of Creatures:</x-core.dl.dt>
              <x-core.dl.dd>{{ $guideQuest->required_event_goal_participation }}</x-core.dl.dd>
            @endif

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
