<x-core.cards.card-with-title title="Guide Settings">
    <p class="mb-4">
        Enabling the guide can only be done while you are level 10 or below. Once enabled, The Guide cannot be disabled. You can ignore it, it's just a button up in the
        navigation that you can click (Green button states: Guide Quests), follow the story and the quests to learn more about the game. Completely optional, but comes with rewards.
    </p>
    <p class="mb-4">
        <strong>After enabling this, you cannot disable the guide. See below for why.</strong>
    </p>
    <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
    <form action={{route('user.settings.enable-guide', ['user' => $user->id])}} method="POST">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="custom-checkbox mb-5" for="guide_enabled">
                    <input type="hidden" name="guide_enabled" value="0"/>
                    <input type="checkbox" id="guide_enabled" name="guide_enabled" value="1" {{$user->guide_enabled ? 'checked' : ''}} {{$user->guide_enabled || $user->character->level > 10 ? 'disabled' : ''}}>
                    <span></span>
                    <span>Enable Guide</span>
                </label>
            </div>
            <x-core.alerts.info-alert title="ATTN!">
                <p class="mb-2">By selecting this you are asking to be guided through the game. The idea of The Guide is we do not hand hold you. We give you
                    enough to get going and to complete the quest objectives. It is up to you, while interacting with the features the quest want's you to, to then click the help
                    links and read more about the feature.
                </p>
                <p class="mb-2">You can review your completed guide quests by opening the sidebar,
                    clicking Quest Log and clicking on Completed guide quests.
                </p>
            </x-core.alerts.info-alert>
        </div>

        <x-core.buttons.primary-button type="submit">Update Guide Settings.</x-core.buttons.primary-button>
    </form>
</x-core.cards.card-with-title>
