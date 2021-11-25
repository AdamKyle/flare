<div class="container-fluid">
  <x-tabs.pill-tabs-container>
    <x-tabs.tab
      tab="class-bonus"
      selected="true"
      active="true"
      title="Class Bonus"
    />
    <x-tabs.tab
      tab="base-damage-mod-bonus"
      selected="false"
      active="false"
      title="Base Damage Modifier"
    />
    <x-tabs.tab
      tab="base-healing-mod-bonus"
      selected="false"
      active="false"
      title="Base Healing Modifier"
    />
    <x-tabs.tab
      tab="base-ac-mod-bonus"
      selected="false"
      active="false"
      title="Base AC (Armour Class) Modifier"
    />
    <x-tabs.tab
      tab="fight-time-out-mod-bonus"
      selected="false"
      active="false"
      title="Fight Timeout Modifier"
    />
    <x-tabs.tab
      tab="move-time-out-mod-bonus"
      selected="false"
      active="false"
      title="Move Timeout Modifier"
    />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="class-bonus"
      active="true"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes raise the characters class bonus. The higher the bonus, the higher the chance to fire off your special attack
            which is based off your class skill. These <strong>do not stack</strong>, how ever other aspects such as skills,
            stats and damage that can stack, will stack. Other Affixes that raise your Class Bonus may be listed here.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'class_bonus',
            'type' => 'class_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="base-damage-mod-bonus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes increase your Base Damage Modifier. This amplifies your damage.
            These do stack, so having more is often better then having none.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'modifiers',
            'type' => 'base_damage_mod_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="base-healing-mod-bonus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes increase your Base Healing Modifier. This amplifies your healing.
            These do stack, so having more is often better then having none.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'modifiers',
            'type' => 'base_healing_mod_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="base-ac-mod-bonus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes increase your Base Armour Class (AC). This amplifies your ability to block damage.
            These do stack, so having more is often better then having none.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'modifiers',
            'type' => 'base_ac_mod_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="fight-time-out-mod-bonus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes reduce the amount of time on the Fight Timer after you successfully kill a critter from the drop down.
            Your time out will never go below 1 second, even if you have over 100% on the Fight Timeout Bonus.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'modifiers',
            'type' => 'fight_time_out_mod_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="move-time-out-mod-bonus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes can effect your move time out. These can reduce the amount of time you have to wait between being able to move again.
            These do stack and will never let you go below 1 second for movement.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'modifiers',
            'type' => 'irresistible_damage',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
