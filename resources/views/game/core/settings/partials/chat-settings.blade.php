<x-core.cards.card-with-title title="Chat Settings">
    <p class="mb-4">
        Here you can manage what notifications you see in chat. Some are off by default, because they can get annoying.
    </p>
    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
    <form action={{route('user.settings.chat', ['user' => $user->id])}} method="POST">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="show_unit_recruitment_messages">
                    <input type="hidden" name="show_unit_recruitment_messages" value="0"/>
                    <input type="checkbox" id="show_unit_recruitment_messages" name="show_unit_recruitment_messages" value="1" {{$user->show_unit_recruitment_messages ? 'checked' : ''}}>
                    <span></span>
                    <span>Unit Recruitment</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By selecting this, you are saying you want chat notifications about unit recruitment for all kingdoms.
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="show_building_upgrade_messages">
                    <input type="hidden" name="show_building_upgrade_messages" value="0"/>
                    <input type="checkbox" id="show_building_upgrade_messages" name="show_building_upgrade_messages" value="1" {{$user->show_building_upgrade_messages ? 'checked' : ''}}>
                    <span></span>
                    <span>Building Upgrades</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By selecting this, you are saying you want chat notifications about building upgrades for all kingdoms.
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="show_building_rebuilt_messages">
                    <input type="hidden" name="show_building_rebuilt_messages" value="0"/>
                    <input type="checkbox" id="show_building_rebuilt_messages" name="show_building_rebuilt_messages" value="1" {{$user->show_building_rebuilt_messages ? 'checked' : ''}}>
                    <span></span>
                    <span>Buildings Rebuilt</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By selecting this, you are saying you want chat notifications about buildings that finished being rebuilt, for all kingdoms.
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="show_kingdom_update_messages">
                    <input type="hidden" name="show_kingdom_update_messages" value="0"/>
                    <input type="checkbox" id="show_kingdom_update_messages" name="show_kingdom_update_messages" value="1" {{$user->show_kingdom_update_messages ? 'checked' : ''}}>
                    <span></span>
                    <span>Hourly Kingdom Notices</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By selecting this, you are saying you want chat notifications for when the hourly reset happens.
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="show_monster_to_low_level_message">
                    <input type="hidden" name="show_monster_to_low_level_message" value="0"/>
                    <input type="checkbox" id="show_monster_to_low_level_message" name="show_monster_to_low_level_message" value="1" {{$user->show_monster_to_low_level_message ? 'checked' : ''}}>
                    <span></span>
                    <span>Monster to low level message</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                By selecting this, you are stating you want to be alerted, via the Server Message tab, when a monster is to low level for you.
            </x-core.alerts.info-alert>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>


        <x-core.buttons.primary-button type="submit">Update Chat Settings.</x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
