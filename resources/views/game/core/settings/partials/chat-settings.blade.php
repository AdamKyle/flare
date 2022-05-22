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
            <div class="border-b-blue-500 rounded-md p-2 bg-blue-200">
                By selecting this, you are saying you want chat notifications about unit recruitment for all kingdoms.
            </div>
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
            <div class="border-b-blue-500 rounded-md p-2 bg-blue-200">
                By selecting this, you are saying you want chat notifications about building upgrades for all kingdoms.
            </div>
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
            <div class="border-b-blue-500 rounded-md p-2 bg-blue-200">
                By selecting this, you are saying you want chat notifications about buildings that finished being rebuilt, for all kingdoms.
            </div>
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
            <div class="border-b-blue-500 rounded-md p-2 bg-blue-200">
                By selecting this, you are saying you want chat notifications for when the hourly reset happens.
            </div>
        </div>
        <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>


        <x-core.buttons.primary-button type="submit">Update Chat Settings.</x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
