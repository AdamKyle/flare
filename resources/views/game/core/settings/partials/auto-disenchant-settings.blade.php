
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-core.cards.card-with-title title="Auto Disenchant Settings">
            <p class="mb-4">
                Here you may set up your auto disenchanting. This is useful for high level characters
                who can get to the Shadow Plane and receive large amounts of drops.
            </p>
            <p class="mb-4">
                Auto disenchant will work across planes - but <strong>not</strong> for adventures.
                We will <strong>never, ever</strong>, disenchant quest drops as they cannot be disenchanted or destroyed.
            </p>
            <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
            <form action={{route('user.settings.auto-disenchant', ['user' => $user->id])}} method="POST">
                @csrf

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="custom-checkbox mb-5" for="auto_disenchant">
                            <input type="hidden" name="auto_disenchant" value="0"/>
                            <input type="checkbox" id="auto_disenchant" name="auto_disenchant" value="1" {{$user->auto_disenchant ? 'checked' : ''}}>
                            <span></span>
                            <span>Auto Disenchant?</span>
                        </label>
                    </div>
                    <x-core.alerts.info-alert title="ATTN!">
                        By selecting this you are saying you want to auto disenchant items as they drop instead of collecting them.
                        <strong>New players are advised to not enable this as the low level drops can be useful to you</strong>.
                    </x-core.alerts.info-alert>
                </div>
                <div class='border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3'></div>
                <div class="grid md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <div class="mb-5">
                            <label class="label block mb-2" for="auto_disenchant_amount">Auto Disenchant Amount </label>
                            <select class="form-control" name="auto_disenchant_amount" id="auto_disenchant_amount" value="{{$user->auto_disenchant_amount}}">
                                <option value="">Please select</option>
                                <option value="all" {{$user->auto_disenchant_amount === 'all' ? 'selected' : ''}}>All</option>
                                <option value="1-billion" {{$user->auto_disenchant_amount === '1-billion' ? 'selected' : ''}}>Keep items with value of 1 Billion</option>
                            </select>
                        </div>
                    </div>
                    <x-core.alerts.info-alert title="ATTN!">
                        <p class="mb-4">
                            Choosing <strong>Disenchant All</strong> will ignore the items value and just disenchant it.
                        </p>
                        <p class="mb-4">
                            Choosing <strong>Keep items With Value of 1 Billion Gold (useful for Shadow Plane)</strong> will only keep items who's item cost with combined affixes
                            is or is above 1 Billion Gold.
                        </p>
                        <p class="mb-4">
                            If you are slightly under geared and head to Shadow Plane, select the last option to get the gear you want. Remember, any item
                            and any affix can drop in combination in the Shadow Plane as long as the creature is 10 levels higher than you.
                        </p>
                    </x-core.alerts.info-alert>
                </div>

                <x-core.buttons.primary-button type="submit">
                    Update Auto Disenchant Settings.
                </x-core.buttons.primary-button>
            </form>
        </x-core.cards.card-with-title>
    </div>
</div>
