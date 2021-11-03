
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Auto Disenchant Settings">
            <div class="row justify-content-center mb-3">
                <div class="col-md-8">
                    <div class="alert alert-info">
                        <p>
                            Here you may set up your auto disenchanting. This is useful for high level characters
                            who can get to the Shadow Plane and receive large amounts of drops.
                        </p>
                        <p>
                            Auto disenchant will work across planes - but <strong>not</strong> for adventures.
                            We will <strong>never, ever</strong>, disenchant quest drops as they cannot be disenchanted or destroyed.
                        </p>
                    </div>
                </div>
            </div>
            <form action={{route('user.settings.auto-disenchant', ['user' => $user->id])}} method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="auto_disenchant" class="form-check-input" type="checkbox" data-toggle="toggle" name="auto_disenchant" value="1" {{$user->auto_disenchant ? 'checked' : ''}}>
                            <label for="auto_disenchant" class="form-check-label ml-2">Auto Disenchant</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this you are saying you want to auto disenchant items as they drop instead of collecting them. <strong>New players are advised to
                            not enable this as the low level drops can be useful to you</strong>.
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <label for="auto_disenchant_amount">Don't disenchant for?</label>
                        <select class="form-control" id="auto_disenchant_amount" name="auto_disenchant_amount">
                            <option value="" {{is_null($user->auto_disenchant_amount) ? 'selected' : ''}}>Please select</option>
                            <option value="all" {{$user->auto_disenchant_amount === 'all' ? 'selected' : ''}}>Disenchant All</option>
                            <option value="1-billion" {{$user->auto_disenchant_amount === '1-billion' ? 'selected' : ''}}>Keep items With Value of 1 Billion Gold (useful for Shadow Plane)</option>
                        </select>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        <p>
                            Choosing <strong>Disenchant All</strong> will ignore the items value and just disenchant it.
                        </p>
                        <p>
                            Choosing <strong>Keep items With Value of 1 Billion Gold (useful for Shadow Plane)</strong> will only keep items who's item cost with combined affixes
                            is or is above 1 Billion Gold.
                        </p>
                        <p>
                            If you are slightly under geared and head to Shadow Plane, select the last option to get the gear you want. Remember, any item
                            and any affix can drop in combination in the Shadow Plane as long as the creature is 10 levels higher then you.
                        </p>
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-6 ml-3">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Update Auto Disenchant Settings') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-cards.card-with-title>
    </div>
</div>
