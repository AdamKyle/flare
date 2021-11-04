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
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies STR and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies STR. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'str_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-dex"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies DEX and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies DEX. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'dex_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-dur"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies DUR and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies DUR. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'dur_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-int"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies INT and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies INT. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'int_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-chr"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies CHR and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies CHR. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'chr_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-agi"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies AGI and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies AGI. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'agi_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="reduces-focus"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These Affixes effect your enemies Focus and reduces it. The more you have the more is reduced. These affixes can stack and may include other affixes in
          the same list that reduce the enemies Focus. Even if an affix would have non stacking elements about it, such as voidance,
          the stat reducing will still stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'focus_reduction',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
