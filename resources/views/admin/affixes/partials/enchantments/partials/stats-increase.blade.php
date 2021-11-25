<div class="container-fluid">
  <x-tabs.pill-tabs-container>
    <x-tabs.tab
      tab="effects-str"
      selected="true"
      active="true"
      title="Raises STR"
    />
    <x-tabs.tab
      tab="effects-dex"
      selected="false"
      active="false"
      title="Raises DEX"
    />
    <x-tabs.tab
      tab="effects-dur"
      selected="false"
      active="false"
      title="Raises DUR"
    />
    <x-tabs.tab
      tab="effects-int"
      selected="false"
      active="false"
      title="Raises INT"
    />
    <x-tabs.tab
      tab="effects-chr"
      selected="false"
      active="false"
      title="Raises CHR"
    />
    <x-tabs.tab
      tab="effects-agi"
      selected="false"
      active="false"
      title="Raises AGI"
    />
    <x-tabs.tab
      tab="effects-focus"
      selected="false"
      active="false"
      title="Raises FOCUS"
    />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="effects-str"
      active="true"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your STR. Other affixes may be included in this list, as they can also effect your STR value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'str_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-dex"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your DEX. Other affixes may be included in this list, as they can also effect your DEX value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'dex_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-dur"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your DUR. Other affixes may be included in this list, as they can also effect your DUR value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'dur_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-int"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your INT. Other affixes may be included in this list, as they can also effect your INT value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'int_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-chr"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your CHR. Other affixes may be included in this list, as they can also effect your CHR value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'chr_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-agi"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your AGI. Other affixes may be included in this list, as they can also effect your AGI value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'agi_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="effects-focus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes will raise your FOCUS. Other affixes may be included in this list, as they can also effect your Focus value.
            All Affixes are multiplicative. Any Affix with a stat increase will stack, even if it has non stacking elements such as damage or voidance
            or even life stealing, depending on your class.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'specific_stat',
            'type' => 'focus_mod',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
