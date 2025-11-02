<p class="mb-4">
  Here you can manage what notifications you see in the Server Messages. Some
  are off by default, because they can get annoying.
</p>
<div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>
<form
  action="{{ route('user.settings.chat', ['user' => $user->id]) }}"
  method="POST"
>
  @csrf

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_unit_recruitment_messages">
        <input type="hidden" name="show_unit_recruitment_messages" value="0" />
        <input
          type="checkbox"
          id="show_unit_recruitment_messages"
          name="show_unit_recruitment_messages"
          value="1"
          {{ $user->show_unit_recruitment_messages ? 'checked' : '' }}
        />
        <span></span>
        <span>Unit Recruitment</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are saying you want server message notifications
      about unit recruitment for all kingdoms.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_building_upgrade_messages">
        <input type="hidden" name="show_building_upgrade_messages" value="0" />
        <input
          type="checkbox"
          id="show_building_upgrade_messages"
          name="show_building_upgrade_messages"
          value="1"
          {{ $user->show_building_upgrade_messages ? 'checked' : '' }}
        />
        <span></span>
        <span>Building Upgrades</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are saying you want server message notifications
      about building upgrades for all kingdoms.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_building_rebuilt_messages">
        <input type="hidden" name="show_building_rebuilt_messages" value="0" />
        <input
          type="checkbox"
          id="show_building_rebuilt_messages"
          name="show_building_rebuilt_messages"
          value="1"
          {{ $user->show_building_rebuilt_messages ? 'checked' : '' }}
        />
        <span></span>
        <span>Buildings Rebuilt</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are saying you want server message notifications
      about buildings that finished being rebuilt, for all kingdoms.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_kingdom_update_messages">
        <input type="hidden" name="show_kingdom_update_messages" value="0" />
        <input
          type="checkbox"
          id="show_kingdom_update_messages"
          name="show_kingdom_update_messages"
          value="1"
          {{ $user->show_kingdom_update_messages ? 'checked' : '' }}
        />
        <span></span>
        <span>Hourly Kingdom Notices</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are saying you want server message notifications
      for when the hourly reset happens.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label
        class="custom-checkbox mb-5"
        for="show_monster_to_low_level_message"
      >
        <input
          type="hidden"
          name="show_monster_to_low_level_message"
          value="0"
        />
        <input
          type="checkbox"
          id="show_monster_to_low_level_message"
          name="show_monster_to_low_level_message"
          value="1"
          {{ $user->show_monster_to_low_level_message ? 'checked' : '' }}
        />
        <span></span>
        <span>Monster to low level message</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted, via the Server
      Message tab, when a monster is to low level for you.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_xp_for_exploration">
        <input type="hidden" name="show_xp_for_exploration" value="0" />
        <input
          type="checkbox"
          id="show_xp_for_exploration"
          name="show_xp_for_exploration"
          value="1"
          {{ $user->show_xp_for_exploration ? 'checked' : '' }}
        />
        <span></span>
        <span>Show xp gained during exploration</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted of xp gained
      during exploration. This will be a total of the xp given to you based on
      the number of creatures you killed.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_xp_per_kill">
        <input type="hidden" name="show_xp_per_kill" value="0" />
        <input
          type="checkbox"
          id="show_xp_per_kill"
          name="show_xp_per_kill"
          value="1"
          {{ $user->show_xp_per_kill ? 'checked' : '' }}
        />
        <span></span>
        <span>Show xp gained per kill</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted of xp gained out
      side of exploration on a per kill basis.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_xp_for_class_masteries">
        <input type="hidden" name="show_xp_for_class_masteries" value="0" />
        <input
          type="checkbox"
          id="show_xp_for_class_masteries"
          name="show_xp_for_class_masteries"
          value="1"
          {{ $user->show_xp_for_class_masteries ? 'checked' : '' }}
        />
        <span></span>
        <span>Show xp gained for class masteries</span>
      </label>
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
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_xp_for_class_ranks">
        <input type="hidden" name="show_xp_for_class_ranks" value="0" />
        <input
          type="checkbox"
          id="show_xp_for_class_ranks"
          name="show_xp_for_class_ranks"
          value="1"
          {{ $user->show_xp_for_class_ranks ? 'checked' : '' }}
        />
        <span></span>
        <span>Show xp gained for class ranks</span>
      </label>
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
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label
        class="custom-checkbox mb-5"
        for="show_xp_for_equipped_class_specials"
      >
        <input
          type="hidden"
          name="show_xp_for_equipped_class_specials"
          value="0"
        />
        <input
          type="checkbox"
          id="show_xp_for_equipped_class_specials"
          name="show_xp_for_equipped_class_specials"
          value="1"
          {{ $user->show_xp_for_equipped_class_specials ? 'checked' : '' }}
        />
        <span></span>
        <span>Show xp gained for equipped class specials</span>
      </label>
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
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_faction_point_message">
        <input type="hidden" name="show_faction_point_message" value="0" />
        <input
          type="checkbox"
          id="show_faction_point_message"
          name="show_faction_point_message"
          value="1"
          {{ $user->show_faction_point_message ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Faction Point Gain</span>
      </label>
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

  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_faction_loyalty_xp_gain">
        <input type="hidden" name="show_faction_loyalty_xp_gain" value="0" />
        <input
          type="checkbox"
          id="show_faction_loyalty_xp_gain"
          name="show_faction_loyalty_xp_gain"
          value="1"
          {{ $user->show_faction_loyalty_xp_gain ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Faction Loyalty XP Gain</span>
      </label>
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
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_skill_xp_per_kill">
        <input type="hidden" name="show_skill_xp_per_kill" value="0" />
        <input
          type="checkbox"
          id="show_skill_xp_per_kill"
          name="show_skill_xp_per_kill"
          value="1"
          {{ $user->show_skill_xp_per_kill ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Training Skill XP Gain</span>
      </label>
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
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_item_skill_kill_count">
        <input type="hidden" name="show_item_skill_kill_count" value="0" />
        <input
          type="checkbox"
          id="show_item_skill_kill_count"
          name="show_item_skill_kill_count"
          value="1"
          {{ $user->show_item_skill_kill_count ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Item Skill Kill Count</span>
      </label>
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
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_gold_per_kill">
        <input type="hidden" name="show_gold_per_kill" value="0" />
        <input
          type="checkbox"
          id="show_gold_per_kill"
          name="show_gold_per_kill"
          value="1"
          {{ $user->show_gold_per_kill ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Gold Gained Per Kill</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted gold you gain
      per kill.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_gold_dust_per_kill">
        <input type="hidden" name="show_gold_dust_per_kill" value="0" />
        <input
          type="checkbox"
          id="show_gold_dust_per_kill"
          name="show_gold_dust_per_kill"
          value="1"
          {{ $user->show_gold_dust_per_kill ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Gold Dust Per Kill</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted gold dust you
      gain per kill.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_shards_per_kill">
        <input type="hidden" name="show_shards_per_kill" value="0" />
        <input
          type="checkbox"
          id="show_shards_per_kill"
          name="show_shards_per_kill"
          value="1"
          {{ $user->show_shards_per_kill ? 'checked' : '' }}
        />
        <span></span>
        <span>Show (Crystal) Shards Gained Per Kill</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted (crystal) shards
      you gain per kill.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <div class="grid grid-cols-2 gap-4">
    <div>
      <label class="custom-checkbox mb-5" for="show_copper_coins_per_kill">
        <input type="hidden" name="show_copper_coins_per_kill" value="0" />
        <input
          type="checkbox"
          id="show_copper_coins_per_kill"
          name="show_copper_coins_per_kill"
          value="1"
          {{ $user->show_copper_coins_per_kill ? 'checked' : '' }}
        />
        <span></span>
        <span>Show Copper Coins Gained Per Kill</span>
      </label>
    </div>
    <x-core.alerts.info-alert title="ATTN!">
      By selecting this, you are stating you want to be alerted copper coins you
      gain per kill.
    </x-core.alerts.info-alert>
  </div>
  <div class="my-3 border-b-2 border-b-gray-300 dark:border-b-gray-600"></div>

  <x-core.buttons.primary-button type="submit">
    Update Server Message Settings.
  </x-core.buttons.primary-button>
</form>
