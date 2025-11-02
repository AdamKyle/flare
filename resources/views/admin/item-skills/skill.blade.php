@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    @php
      $backUrl = route('admin.items-skills.list');

      if (is_null(auth()->user())) {
        $backUrl = '/';
      } elseif (
        ! auth()
          ->user()
          ->hasRole('Admin')
      ) {
        $backUrl = '/';
      }
    @endphp

    <x-core.cards.card-with-title
      title="{{$itemSkill->name}}"
      buttons="true"
      backUrl="{{$backUrl}}"
      editUrl="{{route('admin.items-skills.edit', ['itemSkill' => $itemSkill])}}"
    >
      <p class="mt-4 mb-4">{{ $itemSkill->description }}</p>
      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>
      <div class="grid gap-2 md:grid-cols-2">
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">Stat Modifiers</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Str Modifier:</dt>
            <dd>{{ $itemSkill->str_mod * 100 }}%</dd>
            <dt>Dex Modifier:</dt>
            <dd>{{ $itemSkill->dex_mod * 100 }}%</dd>
            <dt>Dur Modifier:</dt>
            <dd>{{ $itemSkill->dur_mod * 100 }}%</dd>
            <dt>Int Modifier:</dt>
            <dd>{{ $itemSkill->int_mod * 100 }}%</dd>
            <dt>Chr Modifier:</dt>
            <dd>{{ $itemSkill->chr_mod * 100 }}%</dd>
            <dt>Agi Modifier:</dt>
            <dd>{{ $itemSkill->agi_mod * 100 }}%</dd>
            <dt>Focus Modifier:</dt>
            <dd>{{ $itemSkill->focus_mod * 100 }}%</dd>
          </dl>
        </div>
        <div
          class="my-3 block border-b-2 border-b-gray-300 md:hidden dark:border-b-gray-600"
        ></div>
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">
            Damage/AC/Healing Modifiers
          </h3>
          <x-core.separator.separator />
          <dl>
            <dt>Base Attack Modifier:</dt>
            <dd>{{ $itemSkill->base_damage_mod * 100 }}%</dd>
            <dt>Base AC Modifier:</dt>
            <dd>{{ $itemSkill->base_ac_mod * 100 }}%</dd>
            <dt>Base Healing Modifier:</dt>
            <dd>{{ $itemSkill->base_healing_mod * 100 }}%</dd>
          </dl>
          <x-core.separator.separator />
          <h3 class="text-sky-600 dark:text-sky-500">Level Info</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Max Level:</dt>
            <dd>{{ $itemSkill->max_level }}</dd>
            <dt>Total Kills Per Level:</dt>
            <dd>{{ $itemSkill->total_kills_needed }}</dd>
            <dt>Unlocks at Parent Skill Level:</dt>
            <dd>
              {{ ! is_null($itemSkill->parent_level_needed) ? $itemSkill->parent_level_needed : 'N/A' }}
            </dd>
            <dt>Parent Skill</dt>
            @if (! is_null($itemSkill->parent))
              <dd>
                @if (! auth()->user())
                  <a
                    href="/information/item-skills/skill/{{ $itemSkill->id }}"
                    target="_blank"
                  >
                    <i class="fas fa-external-link-alt"></i>
                    {{ $itemSkill->name }}
                  </a>
                @elseif (! auth()->user()->hasRole('Admin'))
                  <a
                    href="/information/item-skills/skill/{{ $itemSkill->id }}"
                    target="_blank"
                  >
                    <i class="fas fa-external-link-alt"></i>
                    {{ $itemSkill->name }}
                  </a>
                @else
                  <a
                    href="/admin/item-skills/{{ $itemSkill->id }}"
                    target="_blank"
                  >
                    <i class="fas fa-external-link-alt"></i>
                    {{ $itemSkill->name }}
                  </a>
                @endif
              </dd>
            @else
              <dd>N/A</dd>
            @endif
          </dl>
        </div>
      </div>
      @if ($itemSkill->children()->count() > 0)
        <div class="my-4">
          <x-core.separator.separator />
          <h3 class="text-sky-600 dark:text-sky-500">Child Skills</h3>
          <x-core.separator.separator />

          @livewire(
            'admin.item-skills.item-skills-table',
            [
              'parentSkill' => $itemSkill->id,
            ]
          )
        </div>
      @endif
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
