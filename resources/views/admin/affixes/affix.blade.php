@extends('layouts.app')

@section('content')
  <x-core.layout.info-container>
    @php
      $backUrl = route('affixes.list');

      if (is_null(auth()->user())) {
        $backUrl = '/information/enchanting';
      } elseif (
        ! auth()
          ->user()
          ->hasRole('Admin')
      ) {
        $backUrl = '/information/enchanting';
      }
    @endphp

    <x-core.cards.card-with-title
      title="{{$itemAffix->name}} ({{$itemAffix->type}})"
      buttons="true"
      backUrl="{{$backUrl}}"
      editUrl="{{route('affixes.edit', ['affix' => $itemAffix->id])}}"
    >
      <p class="mt-4 mb-4">{{ $itemAffix->description }}</p>

      <div class="grid gap-2 md:grid-cols-3">
        @if ($itemAffix->str_mod > 0 ||
          $itemAffix->dex_mod > 0 ||
          $itemAffix->dur_mod > 0 ||
          $itemAffix->int_mod > 0 ||
          $itemAffix->chr_mod > 0 ||
          $itemAffix->agi_mod > 0 ||
          $itemAffix->focus_mod > 0)
          <div>
            <h3 class="text-sky-600 dark:text-sky-500">Stat Modifiers</h3>
            <div
              class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <dl>
              @if ($itemAffix->str_mod > 0)
                <dt>Str Modifier:</dt>
                <dd>{{ $itemAffix->str_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->dex_mod > 0)
                <dt>Dex Modifier:</dt>
                <dd>{{ $itemAffix->dex_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->dur_mod > 0)
                <dt>Dur Modifier:</dt>
                <dd>{{ $itemAffix->dur_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->int_mod > 0)
                <dt>Int Modifier:</dt>
                <dd>{{ $itemAffix->int_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->chr_mod > 0)
                <dt>Chr Modifier:</dt>
                <dd>{{ $itemAffix->chr_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->agi_mod > 0)
                <dt>Agi Modifier:</dt>
                <dd>{{ $itemAffix->agi_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->focus_mod > 0)
                <dt>Focus Modifier:</dt>
                <dd>{{ $itemAffix->focus_mod * 100 }}%</dd>
              @endif
            </dl>
          </div>
        @endif

        @if ($itemAffix->str_reduction > 0 ||
          $itemAffix->dex_reduction > 0 ||
          $itemAffix->dur_reduction > 0 ||
          $itemAffix->int_reduction > 0 ||
          $itemAffix->chr_reduction > 0 ||
          $itemAffix->agi_reduction > 0 ||
          $itemAffix->focus_reduction > 0)
          <div>
            <h3 class="text-sky-600 dark:text-sky-500">Enemy Stat Reduction</h3>
            <div
              class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <dl>
              @if ($itemAffix->str_reduction > 0)
                <dt>Str Reduction:</dt>
                <dd>{{ $itemAffix->str_reduction * 100 }}%</dd>
              @endif

              @if ($itemAffix->dex_reduction > 0)
                <dt>Dex Reduction:</dt>
                <dd>{{ $itemAffix->dex_reduction * 100 }}%</dd>
              @endif

              @if ($itemAffix->dur_reduction > 0)
                <dt>Dur Reduction:</dt>
                <dd>{{ $itemAffix->dur_reduction * 100 }}%</dd>
              @endif

              @if ($itemAffix->int_reduction > 0)
                <dt>Int Reduction:</dt>
                <dd>{{ $itemAffix->int_reduction * 100 }}%</dd>
              @endif

              @if ($itemAffix->chr_reduction > 0)
                <dt>Chr Reduction:</dt>
                <dd>{{ $itemAffix->chr_reduction * 100 }}%</dd>
              @endif

              @if ($itemAffix->agi_reduction > 0)
                <dt>Agi Reduction:</dt>
                <dd>{{ $itemAffix->agi_reduction * 100 }}%</dd>
              @endif

              @if ($itemAffix->focus_reduction > 0)
                <dt>Focus Reduction:</dt>
                <dd>{{ $itemAffix->focus_reduction * 100 }}%</dd>
              @endif
            </dl>
          </div>
        @endif

        @if ($itemAffix->base_damage_mod > 0 || $itemAffix->base_ac_mod > 0 || $itemAffix->base_healing_mod > 0)
          <div>
            <h3 class="text-sky-600 dark:text-sky-500">
              Damage/AC/Healing Modifiers
            </h3>
            <div
              class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
            ></div>
            <dl>
              @if ($itemAffix->base_damage_mod > 0)
                <dt>Base Attack Modifier:</dt>
                <dd>{{ $itemAffix->base_damage_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->base_ac_mod > 0)
                <dt>Base AC Modifier:</dt>
                <dd>{{ $itemAffix->base_ac_mod * 100 }}%</dd>
              @endif

              @if ($itemAffix->base_healing_mod > 0)
                <dt>Base Healing Modifier:</dt>
                <dd>{{ $itemAffix->base_healing_mod * 100 }}%</dd>
              @endif
            </dl>
          </div>
        @endif
      </div>

      <div
        class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"
      ></div>

      @if ($itemAffix->skill_name || $itemAffix->skill_training_bonus > 0 || $itemAffix->skill_bonus > 0)
        <div>
          <h3 class="text-sky-600 dark:text-sky-500">Skill Modifiers</h3>
          <x-core.separator.separator />
          <dl>
            <dt>Skill Name:</dt>
            <dd>
              {{ is_null($itemAffix->skill_name) ? 'N/A' : $itemAffix->skill_name }}
            </dd>
            @if ($itemAffix->skill_training_bonus > 0)
              <dt>Skill XP Bonus (When Training):</dt>
              <dd>{{ $itemAffix->skill_training_bonus * 100 }}%</dd>
            @endif

            @if ($itemAffix->skill_bonus > 0)
              <dt>Skill Bonus (When Using):</dt>
              <dd>{{ $itemAffix->skill_bonus * 100 }}%</dd>
            @endif
          </dl>
        </div>
      @endif
    </x-core.cards.card-with-title>

    @if ($itemAffix->damage_amount > 0)
      <x-core.cards.card-with-title title="Damage Info" buttons="false">
        <x-core.alerts.warning-alert title="ATTN!">
          <p class="my-2">
            Some damage-based affixes can stack - which is outlined below. Those
            that do stack have a chance to be resisted by the enemy.
          </p>
          <p class="mb-2">
            Those that do not stack are irresistible and cannot be blocked.
          </p>
          <p>Damage is a % of your weapon damage.</p>
        </x-core.alerts.warning-alert>
        <dl>
          <dt>Damage:</dt>
          <dd>{{ $itemAffix->damage_amount * 100 }}%</dd>
          <dt>Is Damage Irresistible?:</dt>
          <dd>
            {{ $itemAffix->irresistible_damage ? 'Yes' : 'No' }}
          </dd>
          <dt>Can Stack:</dt>
          <dd>{{ $itemAffix->damage_can_stack ? 'Yes' : 'No' }}</dd>
        </dl>
      </x-core.cards.card-with-title>
    @endif

    @if (! is_null($itemAffix->steal_life_amount))
      <x-core.cards.card-with-title title="Life Stealing" buttons="false">
        <x-core.alerts.warning-alert title="ATTN!">
          These affixes will only stack for Vampire classes. For other classes,
          the Lifestealing Affix attached will be used. Durability stacks for
          all classes.
        </x-core.alerts.warning-alert>
        <dl>
          <dt>Steal Life Amount:</dt>
          <dd>{{ $itemAffix->steal_life_amount * 100 }}%</dd>
        </dl>
      </x-core.cards.card-with-title>
    @endif

    @if ($itemAffix->entranced_chance > 0)
      <x-core.cards.card-with-title title="Entrance Chance" buttons="false">
        <x-core.alerts.warning-alert title="ATTN!">
          These Affixes will not stack. Entrancing the enemy makes it so your
          attack cannot be blocked and will not miss.
        </x-core.alerts.warning-alert>
        <dl>
          <dt>Entrance Chance:</dt>
          <dd>{{ $itemAffix->entranced_chance * 100 }}%</dd>
        </dl>
      </x-core.cards.card-with-title>
    @endif

    @if ($itemAffix->devouring_light > 0)
      <x-core.cards.card-with-title title="Devouring Light" buttons="false">
        <x-core.alerts.warning-alert title="ATTN!">
          Devouring light prevents enemies from using enchantments and life
          stealing, and prevents them from voiding your enchantments.
        </x-core.alerts.warning-alert>
        <dl>
          <dt>Devouring Light Chance:</dt>
          <dd>{{ $itemAffix->devouring_light * 100 }}%</dd>
        </dl>
      </x-core.cards.card-with-title>
    @endif

    @if ($itemAffix->skill_reduction > 0)
      <x-core.cards.card-with-title
        title="Enemy Skill Reduction"
        buttons="false"
      >
        <x-core.alerts.warning-alert title="ATTN!">
          These affixes reduce enemy skills like Accuracy, Dodge, Casting
          Accuracy, and Criticality.
        </x-core.alerts.warning-alert>
        <dl>
          <dt>Skills Reduced By:</dt>
          <dd>{{ $itemAffix->skill_reduction * 100 }}%</dd>
        </dl>
      </x-core.cards.card-with-title>
    @endif

    @if ($itemAffix->resistance_reduction > 0)
      <x-core.cards.card-with-title
        title="Enemy Resistance Reduction"
        buttons="false"
      >
        <x-core.alerts.warning-alert title="ATTN!">
          These affixes reduce specific resistances, making it easier for
          certain abilities to land.
        </x-core.alerts.warning-alert>
        <dl>
          <dt>Resistance Reduction:</dt>
          <dd>{{ $itemAffix->resistance_reduction * 100 }}%</dd>
        </dl>
      </x-core.cards.card-with-title>
    @endif

    <x-core.cards.card-with-title title="Enchanting Info" buttons="false">
      <dl>
        <dt>Base Cost:</dt>
        <dd>{{ number_format($itemAffix->cost) }} Gold</dd>
        <dt>Intelligence Required:</dt>
        <dd>{{ number_format($itemAffix->int_required) }}</dd>
        <dt>Level Required:</dt>
        <dd>{{ $itemAffix->skill_level_required }}</dd>
        <dt>Level Trivial:</dt>
        <dd>{{ $itemAffix->skill_level_trivial }}</dd>
      </dl>
    </x-core.cards.card-with-title>
  </x-core.layout.info-container>
@endsection
