<div class="row justify-content-center">
  <div class="col-md-12">
    <p class="mb-4">
      Here you may set up your auto disenchanting. This is useful for high level
      characters who can get to the Shadow Plane and receive large amounts of
      drops.
    </p>
    <p class="mb-4">
      Auto disenchant will work across planes - but
      <strong>not</strong>
      for adventures. We will
      <strong>never, ever</strong>
      , disenchant quest drops as they cannot be disenchanted or destroyed.
    </p>
    <x-core.separator.separator />
    <form
      action="{{ route('user.settings.auto-disenchant', ['user' => $user->id]) }}"
      method="POST"
    >
      @csrf

      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <x-form-elements.check-box name="auto_disenchant" label="Auto Disenchant?" :model="$user" model-key="auto_disenchant" />
        </div>
        <x-core.alerts.info-alert title="ATTN!">
          By selecting this you are saying you want to auto disenchant items as
          they drop instead of collecting them.
          <strong>
            New players are advised to not enable this as the low level drops
            can be useful to you
          </strong>
          .
        </x-core.alerts.info-alert>
      </div>
      <x-core.separator.separator />
      <div class="grid gap-4 md:grid-cols-2">
        <div>
          <x-form-elements.check-box name="auto_sell_item" label="Auto Sell?" :model="$user" model-key="auto_sell_item" />
        </div>
        <x-core.alerts.info-alert title="ATTN!">
          By selecting this you are saying you want to auto sell items you
          cannot disenchant because you are Gold Dust capped.
        </x-core.alerts.info-alert>
      </div>
      <x-core.separator.separator />
      <div class="mb-4 grid gap-4 md:grid-cols-2">
        <div>

          <x-form-elements.select name="auto_disenchant_amount" label="Auto Disenchant Amount" :model="$user" model-key="auto_disenchant_amount" :options="['all','1-billion']" />

        </div>
        <x-core.alerts.info-alert title="ATTN!">
          <p class="mb-4">
            Choosing
            <strong>Disenchant All</strong>
            will ignore the items value and just disenchant it.
          </p>
          <p class="mb-4">
            Choosing
            <strong>
              Keep items With Value of 1 Billion Gold (useful for Shadow Plane)
            </strong>
            will only keep items who's item cost with combined affixes is or is
            above 1 Billion Gold.
          </p>
          <p class="mb-4">
            If you are slightly under geared and head to Shadow Plane, select
            the last option to get the gear you want. Remember, any item and any
            affix can drop in combination in the Shadow Plane as long as the
            creature is 10 levels higher than you.
          </p>
        </x-core.alerts.info-alert>
      </div>

      <x-core.buttons.primary-button type="submit">
        Update Auto Disenchant Settings.
      </x-core.buttons.primary-button>
    </form>
  </div>
</div>
