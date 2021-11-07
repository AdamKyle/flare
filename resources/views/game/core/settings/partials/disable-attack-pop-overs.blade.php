
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Auto Disenchant Settings">
            <div class="row justify-content-center mb-3">
                <div class="col-md-8">
                    <div class="alert alert-info">
                        <p>
                            You can enable this to disable the popovers for the attack buttons when fighting monsters.
                        </p>
                    </div>
                </div>
            </div>
            <form action="{{route('user.settings.disable-attack-pop-overs', ['user' => $user->id])}}" method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="disable_attack_type_popover" class="form-check-input" type="checkbox" data-toggle="toggle" name="disable_attack_type_popover" value="1" {{$user->disable_attack_type_popover ? 'checked' : ''}}>
                            <label for="disable_attack_type_popover" class="form-check-label ml-2">Disable Attack Tool Tips</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this you are saying you no longer wish to see the attack types tool tips. New Players are advised from disabling this till they
                        have a better grasp of the game and it's mechanics</strong>.
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-6 ml-3">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Update Attack Tool Tips') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-cards.card-with-title>
    </div>
</div>
