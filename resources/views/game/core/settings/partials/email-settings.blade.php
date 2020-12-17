
<div class="row justify-content-center">
    <div class="col-md-12">
        <x-cards.card-with-title title="Email Settings">
            <form action={{route('user.settings.email', ['user' => $user->id])}} method="POST">
                @csrf
                
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="adventure" class="form-check-input" type="checkbox" data-toggle="toggle" name="adventure_email" value="1" {{$user->adventure_email ? 'checked' : ''}}>
                            <label for="adventure" class="form-check-label ml-2">Adventure Emails</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying you want to emails relating to your adventure completion.
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check move-down-30">
                            <input id="canSpeakAgain" class="form-check-input" type="checkbox" data-toggle="toggle" name="can_speak_again_email" value="1" {{$user->can_speak_again_email ? 'checked' : ''}}>
                            <label for="canSpeakAgain" class="form-check-label ml-2">Can Speak Again Emails</label>
                        </div>
                    </div>
                    <div class="col-md-8 move-down-30 alert alert-info">
                        By selecting this, you are saying that, should you be silenced and log out, we will email you when you can speak again.
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