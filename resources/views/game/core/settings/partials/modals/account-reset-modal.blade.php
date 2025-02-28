<x-core.modals.modal id="resetAccount" title="Reset Account" formId="account-reset" formActionTitle="Yes. I am sure">
    <p class="mb-4">
        Resetting your account, allows you to pick a new race and class. You can change your name at the top of Account Settings.
    </p>
    <p class="mb-4">
        Upon resetting your account we delete everythig about your account except your user profile. This means you lose all kingdoms, enchanted items,
        currencies, skills, everything. You keep and carry over nothing. You start from level one again.
    </p>
    <p class="mb-4">
        Are you sure you want to do this? There is no going back.
    </p>

    <h3 class="text-sky-600 dark:text-sky-500 mb-4">Optional - Choose new Race and Class.</h3>
    <p class="mb-4 mt-4">
        By selecting any of these options your character will be re-rolled with them. You can reset your account as many times as you want, however, realise you <strong>lose a lot
            of progression when you do that</strong>.
    </p>
    <x-core.alerts.info-alert title="ATTN!">
        Please be aware that the Guide quest will be enabled for all characters and there no way to disabled them.
        You will restart your progress in sdaid guide quests when you re-roll your character.
    </x-core.alerts.info-alert>
    <p class="mb-4">
        By selecting Enable Guide, we will not show you the initial popup, instead: Click the Guide Quest in  the top navigation after resetting the account.
    </p>
    <form action="{{route('reset.account', [
                    'user' => $user
                ])}}" id="account-reset" method="POST">
        @csrf

        <div class="mb-5">
            <label for="races" class="label block mb-2">{{ __('Choose a Race') }}</label>
            <select class="form-control" id="races" name="race">
                <option value="">Please Select (Optional)</option>
                @foreach($races as $id => $name)
                    <option value={{$id}} {{(int) old('race') === (int) $id ? 'selected' : ''}}>{{$name}}</option>
                @endforeach
            </select>

            @error('race')
            <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                <strong>{{$message}}</strong>
            </div>
            @enderror
        </div>
        <div class="mb-5">
            <label for="classes" class="label block mb-2">{{ __('Choose a class') }}</label>

            <select class="form-control" id="classes" name="class">
                <option value="">Please Select (Optional)</option>
                @foreach($classes as $id => $name)
                    <option value="{{$id}}" {{(int) old('class') === (int) $id ? 'selected' : ''}}>{{$name}}</option>
                @endforeach
            </select>

            @error('class')
            <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                <strong>{{$message}}</strong>
            </div>
            @enderror
        </div>
    </form>
</x-core.modals.modal>

@include('auth.partials.guide-quest-help')
