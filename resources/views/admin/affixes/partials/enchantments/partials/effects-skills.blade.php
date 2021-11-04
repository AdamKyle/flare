<div class="container-fluid">
  <x-tabs.pill-tabs-container>
    <x-tabs.tab
      tab="increases-skill-bonus"
      selected="true"
      active="true"
      title="Skill Bonus"
    />
    <x-tabs.tab
      tab="increase-skill-training-bonus"
      selected="false"
      active="false"
      title="Skill Training Bonux (XP)"
    />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="increases-skill-bonus"
      active="true"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes raise your skill bonus. The skill bonus is used to determine, in most cases, if you can hit.
          Some affixes listed here will raise other aspects of the skill as well. Some Affixes may be duplicated here because they both
          raise the skill training (xp) and the skill bonus.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'skills',
            'type' => 'skill_bonus',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="increase-skill-training-bonus"
      active="false"
    >
      <x-cards.card>
        <div class="alert alert-info mt-2 mb-3">
          These affixes raise your skill training (xp) bonus. This bonus is added to your skill xp when training skills.
          Some Affixes may be duplicated here because they both raise the skill training (xp) and the skill bonus.
        </div>

        @livewire('admin.affixes.data-table', [
            'only' => 'skills',
            'type' => 'skill_training_bonus',
        ])
      </x-cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
