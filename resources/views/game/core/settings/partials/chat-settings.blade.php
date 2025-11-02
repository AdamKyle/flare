<p class="mb-4">
  Here you can manage what notifications you see in the Server Messages. Some
  are off by default, because they can get annoying.
</p>
<x-core.separator.separator />
<form
  action="{{ route('user.settings.chat', ['user' => $user->id]) }}"
  method="POST"
>
  @csrf

  <div class="grid grid-cols-2 gap-4">
    <div>

      <x-form-elements.check-box name="show_unit_recruitment_messages" label="Unit Recruitment" :model="$user" model-key="show_unit_recruitment_messages" />

    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are saying you want server message notifications
      about unit recruitment for all kingdoms.
    </x-core.alerts.info-alert>
  </div>
  <x-core.separator.separator />

  <div class="grid grid-cols-2 gap-4">
    <div>

      <x-form-elements.check-box name="show_building_upgrade_messages" label="Building Upgrades" :model="$user" model-key="show_building_upgrade_messages" />

    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are saying you want server message notifications
      about building upgrades for all kingdoms.
    </x-core.alerts.info-alert>
  </div>
  <x-core.separator.separator />

  <div class="grid grid-cols-2 gap-4">
      <div>
  
        <x-form-elements.check-box name="show_building_rebuilt_messages" label="Building Rebuilt" :model="$user" model-key="show_building_rebuilt_messages" />
      </div>

      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are saying you want server message notifications
        about buildings that finished being rebuilt, for all kingdoms.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_kingdom_update_messages" label="Hourly Kingdom Notices" :model="$user" model-key="show_kingdom_update_messages" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are saying you want server message notifications
        for when the hourly reset happens.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_monster_to_low_level_message" label="Monster to low level message" :model="$user" model-key="show_monster_to_low_level_message" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted, via the Server
        Message tab, when a monster is to low level for you.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_xp_for_exploration" label="Show xp gained during exploration" :model="$user" model-key="show_xp_for_exploration" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained
        during exploration. This will be a total of the xp given to you based on
        the number of creatures you killed.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_xp_per_kill" label="Show xp per kill" :model="$user" model-key="show_xp_per_kill" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained out
        side of exploration on a per kill basis.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_xp_for_class_masteries" label="Show xp gained for class masteries" :model="$user" model-key="show_xp_for_class_masteries" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained for
        yuor current classes masteries. These includes things like using a weapon
        and gaining XP towards the mastery of using weapons. you can learn more
        about this through:
        <a href="/information/class-ranks" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          Class Ranks
        </a>
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_xp_for_class_ranks" label="Show xp gained for class ranks" :model="$user" model-key="show_xp_for_class_ranks" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained for
        your
        <a href="/information/class-ranks" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          class rank
        </a>
        .
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_xp_for_equipped_class_specials" label="Show xp gained for equipped class specials" :model="$user" model-key="show_xp_for_equipped_class_specials" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained for
        your
        <a href="/information/class-ranks" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          equiped class specials
        </a>
        .
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_faction_point_message" label="Show Faction points gained" :model="$user" model-key="show_faction_point_message" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of gaining
        points for
        <a href="/information/factions" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          factions
        </a>
        .
      </x-core.alerts.info-alert>
    </div>

    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_faction_loyalty_xp_gain" label="Show Faction loyalty xp gained" :model="$user" model-key="show_faction_loyalty_xp_gain" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained for
        your character when leveling your
        <a href="/information/faction-loyalty" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          Faction Loyalty
        </a>
        .
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_skill_xp_per_kill" label="Show skill xp per kill" :model="$user" model-key="show_skill_xp_per_kill" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of xp gained for
        the current skill you have in
        <a href="/information/skill-information" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          training
        </a>
        . This also includes your
        <a href="/information/class-skills" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          class skill
        </a>
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_item_skill_kill_count" label="Show item skill kill count" :model="$user" model-key="show_item_skill_kill_count" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted of an items
        skill kill count when fighting. This only applies to
        <a href="/information/ancestral-items" target="_blank">
          <i class="fas fa-external-link-alt"></i>
          Ancestral Items
        </a>
        you have equipped.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_gold_per_kill" label="Show gold gained per kill" :model="$user" model-key="show_gold_per_kill" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted gold you gain
        per kill.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_gold_dust_per_kill" label="Show gold dust gained per kill" :model="$user" model-key="show_gold_dust_per_kill" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted gold dust you
        gain per kill.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_shards_per_kill" label="Show shards gained per kill" :model="$user" model-key="show_shards_per_kill" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted (crystal) shards
        you gain per kill.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <div class="grid grid-cols-2 gap-4">
      <div>
        <x-form-elements.check-box name="show_copper_coins_per_kill" label="Show copper coins gained per kill" :model="$user" model-key="show_copper_coins_per_kill" />
      </div>
      <x-core.alerts.info-alert title="ATTN!">
        By selecting this, you are stating you want to be alerted copper coins you
        gain per kill.
      </x-core.alerts.info-alert>
    </div>
    <x-core.separator.separator />

    <x-core.buttons.primary-button type="submit">
      Update Server Message Settings.
    </x-core.buttons.primary-button>
</form>
