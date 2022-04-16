<div class="container-fluid">
  <x-tabs.pill-tabs-container>
    <x-tabs.tab
      tab="reduces-str"
      selected="true"
      active="true"
      title="Decreases STR"
    />
    <x-tabs.tab
      tab="reduces-dex"
      selected="false"
      active="false"
      title="Decreases DEX"
    />
    <x-tabs.tab
      tab="reduces-dur"
      selected="false"
      active="false"
      title="Decreases DUR"
    />
    <x-tabs.tab
      tab="reduces-int"
      selected="false"
      active="false"
      title="Decreases INT"
    />
    <x-tabs.tab
      tab="reduces-chr"
      selected="false"
      active="false"
      title="Decreases CHR"
    />
    <x-tabs.tab
      tab="reduces-agi"
      selected="false"
      active="false"
      title="Decreases AGI"
    />
    <x-tabs.tab
      tab="reduces-focus"
      selected="false"
      active="false"
      title="Decreases FOCUS"
    />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="reduces-str"
      active="true"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's STR and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's STR. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'str_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-dex"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's DEX and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's DEX. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'dex_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-dur"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's DUR and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's DUR. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'dur_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-int"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's INT and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's INT. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'int_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-chr"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's CHR and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's CHR. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'chr_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-agi"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's AGI and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's AGI. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'agi_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-focus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes effect your enemy's Focus and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
            the same list that reduce the enemy's Focus. Even if an affix would have non stacking elements about it, such as voidance,
            the stat reducing will still stack.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'focus_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
