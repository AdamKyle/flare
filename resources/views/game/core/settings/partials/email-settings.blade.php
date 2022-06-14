
<x-core.cards.card-with-title title="Email Settings">
    <p class="mb-4">
        Emails only get sent when you are not online.
        You cannot turn off emails for being banned or for the request of an unban.
        We will never use your email for anything other than what's listed in this box.
    </p>
    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
    <form action={{route('user.settings.email', ['user' => $user->id])}} method="POST">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="upgraded_building_email">
                    <input type="hidden" name="upgraded_building_email" value="0"/>
                    <input type="checkbox" id="upgraded_building_email" name="upgraded_building_email" value="1" {{$user->upgraded_building_email ? 'checked' : ''}}>
                    <span></span>
                    <span>Building Upgrade Email</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                <p class="mb-2">By selecting this, you are saying that, when any building in your queue finishes, you'll get an email.</p>
                <p><strong>Note</strong>: Multiple buildings in queue? You'll get multiple emails, one for each that finishes.</p>
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="rebuilt_building_email">
                    <input type="hidden" name="rebuilt_building_email" value="0"/>
                    <input type="checkbox" id="rebuilt_building_email" name="rebuilt_building_email" value="1" {{$user->rebuilt_building_email ? 'checked' : ''}}>
                    <span></span>
                    <span>Building Upgrade Email</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                <p class="mb-2">By Selecting this, you are saying that, when a building is rebuilt you would like to receive an email.</p>
                <p><strong>Note</strong>: Multiple buildings in queue? You'll get multiple emails, one for each.</p>
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="kingdom_attack_email">
                    <input type="hidden" name="kingdom_attack_email" value="0"/>
                    <input type="checkbox" id="kingdom_attack_email" name="kingdom_attack_email" value="1" {{$user->rebuilt_building_email ? 'checked' : ''}}>
                    <span></span>
                    <span>Kingdom Attack Email(s)</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By Selecting this, you are saying that, if your kingdom is attacked, if you lost an attack, if you were successful in an attack or took a kingdom,
                or even if you lost your kingdom, you will receive an email.
            </x-core.alerts.info-alert>
        </div>

        <x-core.buttons.primary-button type="submit">
            Update Email Settings.
        </x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
