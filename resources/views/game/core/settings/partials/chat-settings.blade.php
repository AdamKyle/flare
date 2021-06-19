
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Chat Settings">
            <div class="row justify-content-center mb-3">
                <div class="col-md-8">
                    <div class="alert alert-info">
                        Here you can manage what notifications you see in chat. Some are off by default, because they can get annoying.
                    </div>
                </div>
            </div>
            <form action={{route('user.settings.chat', ['user' => $user->id])}} method="POST">
                @csrf

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="show_unit_recruitment_messages" class="form-check-input" type="checkbox" data-toggle="toggle" name="show_unit_recruitment_messages" value="1" {{$user->show_unit_recruitment_messages ? 'checked' : ''}}>
                            <label for="show_unit_recruitment_messages" class="form-check-label ml-2">Unit Recruitment</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying you want chat notifications about unit recruitment for all kingdoms.
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="show_building_upgrade_messages" class="form-check-input" type="checkbox" data-toggle="toggle" name="show_building_upgrade_messages" value="1" {{$user->show_building_upgrade_messages ? 'checked' : ''}}>
                            <label for="show_building_upgrade_messages" class="form-check-label ml-2">Building Upgrades</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying you want chat notifications about building upgrades for all kingdoms.
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="show_building_rebuilt_messages" class="form-check-input" type="checkbox" data-toggle="toggle" name="show_building_rebuilt_messages" value="1" {{$user->show_building_upgrade_messages ? 'checked' : ''}}>
                            <label for="show_building_rebuilt_messages" class="form-check-label ml-2">Buildings Rebuilt</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying you want chat notifications about buildings that finished being rebuilt, for all kingdoms.
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="show_kingdom_update_messages" class="form-check-input" type="checkbox" data-toggle="toggle" name="show_kingdom_update_messages" value="1" {{$user->show_kingdom_update_messages ? 'checked' : ''}}>
                            <label for="show_kingdom_update_messages" class="form-check-label ml-2">Hourly Notices</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying you want chat notifications for when the hourly reset happens.
                    </div>
                </div>

                <div class="row mb-0">
                    <div class="col-md-6 ml-3">
                        <button type="submit" class="btn btn-primary">
                            {{ __('Update Chat Settings') }}
                        </button>
                    </div>
                </div>
            </form>
        </x-cards.card-with-title>
    </div>
</div>
