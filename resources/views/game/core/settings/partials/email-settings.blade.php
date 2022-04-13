
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Email Settings">
            <div class="row justify-content-center mb-3">
                <div class="col-md-8">
                    <div class="alert alert-info">
                        Emails only get sent when you are not online.
                        You cannot turn off emails for being banned or for the request of an unban.
                        We will never use your email for anything other then whats listed in this box.
                    </div>
                </div>
            </div>
            <form action={{route('user.settings.email', ['user' => $user->id])}} method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="adventureEmail" class="form-check-input" type="checkbox" data-toggle="toggle" name="adventure_email" value="1" {{$user->adventure_email ? 'checked' : ''}}>
                            <label for="adventureEmail" class="form-check-label ml-2">Adventure Emails</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying you want to emails relating to your adventure completion.
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="upgradedBuildingEmail" class="form-check-input" type="checkbox" data-toggle="toggle" name="upgraded_building_email" value="1" {{$user->upgraded_building_email ? 'checked' : ''}}>
                            <label for="upgradedBuildingEmail" class="form-check-label ml-2">Building Upgrade Email</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying that, when any building in your queue finishes, you'll get an email.
                        <p class="mt-2"><strong>Note</strong>: Multiple buildings in queue? You'll get multiple emails, one for each that finishes.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="rebuiltBuildingEmail" class="form-check-input" type="checkbox" data-toggle="toggle" name="rebuilt_building_email" value="1" {{$user->rebuilt_building_email ? 'checked' : ''}}>
                            <label for="rebuiltBuildingEmail" class="form-check-label ml-2">Building Rebuilt Email</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By Selecting this, you are saying that, when a building is rebuilt you would like to receive an email.
                        <p class="mt-2"><strong>Note</strong>: Multiple buildings in queue? You'll get multiple emails, one for each.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="kingdomAttackEmail" class="form-check-input" type="checkbox" data-toggle="toggle" name="kingdom_attack_email" value="1" {{$user->kingdom_attack_email ? 'checked' : ''}}>
                            <label for="kingdomAttackEmail" class="form-check-label ml-2">Kingdom Attack Email(s)</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By Selecting this, you are saying that, if your kingdom is attacked, if you lost an attack, if you were successful in an attack or took a kingdom,
                        or even if you lost your kingdom, you will receive an email.
                    </div>
                </div>
                <div class="row mb-0">
                    <div class="col-md-6 ml-3">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Update Email Settings') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-cards.card-with-title>
    </div>
</div>
