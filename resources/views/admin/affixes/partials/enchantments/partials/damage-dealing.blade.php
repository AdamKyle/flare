<div class="container-fluid">
  <x-tabs.pill-tabs-container>
    <x-tabs.tab
      tab="damage-dealing-non-irresistible"
      selected="true"
      active="true"
      title="Damage Dealing (Non resistant)"
    />
    <x-tabs.tab
      tab="damage-dealing-irresistible"
      selected="false"
      active="false"
      title="Damage Dealing Resitable"
    />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="damage-dealing-non-irresistible"
      active="true"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes cannot be resisted by the enemy. Some might stack, some might not. Enemies cannot resist this damage.
        </div>

        @livewire('admin.affixes.data-table', [
            'only'         => 'damage-dealing',
            'irresistible' => true,
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="damage-dealing-irresistible"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes can be resisted by enemies, thus they won't do any damage if they are resisted when attacking. Some might stack, others might not.
          Some enchantments may have other aspects about them, such as stats which can stack.
        </div>

        @livewire('admin.affixes.data-table', [
            'only'         => 'damage-dealing',
            'irresistible' => false,
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
