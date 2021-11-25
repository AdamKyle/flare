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
    <x-tabs.tab
      tab="enemy-skill-reduction"
      selected="false"
      active="false"
      title="Skill Reduction (enemies)"
    />
  </x-tabs.pill-tabs-container>
  <x-tabs.tab-content>
    <x-tabs.tab-content-section
      tab="increases-skill-bonus"
      active="true"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes raise your skill bonus. The skill bonus is used to determine, in most cases, if you can hit.
            Some affixes listed here will raise other aspects of the skill as well. Some Affixes may be duplicated here because they both
            raise the skill training (xp) and the skill bonus.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'skills',
            'type' => 'skill_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="increase-skill-training-bonus"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These affixes raise your skill training (xp) bonus. This bonus is added to your skill xp when training skills.
            Some Affixes may be duplicated here because they both raise the skill training (xp) and the skill bonus.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'skills',
            'type' => 'skill_training_bonus',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section
      tab="enemy-skill-reduction"
      active="false"
    >
      <x-core.cards.card>
        <x-core.alerts.info-alert title="Quick Tip">
          <p>
            These Affixes only affect enemies and can reduce ALL their skills at once by a specified %. These affixes work
            in the same vein as stat reduction affixes, how ever these do not stack. We take the best one of all you have on.
          </p>
        </x-core.alerts.info-alert>

        @livewire('admin.affixes.data-table', [
            'only' => 'skills',
            'type' => 'skill_reduction',
        ])
      </x-core.cards.card>
    </x-tabs.tab-content-section>
  </x-tabs.tab-content>
</div>
