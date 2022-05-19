@extends('layouts.app')

@section('content')

    <div class="container flex items-center justify-center mt-20 py-10">
        <div class="w-full md:w-1/2 xl:w-1/3">
            <div class="mx-5 md:mx-10">
                <h2 class="uppercase">Begin your journey</h2>
                <h4 class="uppercase">Let's roll that character up!</h4>
            </div>
            <x-core.cards.form-card css="mt-5 p-5 md:p-10" method="POST" action="{{ route('register') }}">
                @csrf
                <div class="mb-5">
                    <label class="label block mb-2" for="name">{{ __('E-Mail Address') }}</label>
                    <input id="name" type="email" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>
                    @error('email')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mb-5">
                    <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>
                    <input id="password" type="password" class="form-control" name="password" required autocomplete="new-password" autofocus>
                    @error('password')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mb-5">
                    <label for="password-confirm" class="col-md-4 col-form-label text-md-right">{{ __('Confirm Password') }}</label>
                    <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required autocomplete="new-password" autofocus>
                </div>
                <div class="mb-5">
                    <h3>Character Creation</h3>
                    <p class="py-3">
                        Check out <a href="/information/races-and-classes">Races and Classes</a> for more information.
                    </p>
                    <hr />
                </div>
                <div class="mb-5">
                    <label class="label block mb-2" for="name">
                        Name
                        <i class="far fa-question-circle pl-2 cursor"
                           data-toggle="tooltip"
                           data-tippy-placement="right"
                           data-tippy-content="Character names may not contain spaces an can only be 15 characters long (5 characters min) and only contain letters and numbers (of any case)."
                        ></i>
                    </label>
                    <input id="name" type="text" class="form-control" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>
                    @error('name')
                    <div class="text-red-800 dark:text-red-500 pt-3" role="alert">
                        <strong>{{$message}}</strong>
                    </div>
                    @enderror
                </div>
                <div class="mb-5">
                    <label for="races" class="label block mb-2">{{ __('Choose a Race') }}</label>
                    <select class="form-control" id="races" name="race">
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
                <div class="mt-5">
                    <label class="custom-checkbox" for="enable_guide">
                        <input type="checkbox" name="guide_enabled" id="enable_guide">
                        <span></span>
                        <span>Enable Guide? <a href="#no-link" data-toggle="modal" data-target="#guide-explanation">(Help)</a></span>
                    </label>
                </div>
                <div class="flex">
                    <x-core.buttons.primary-button css="ltr:ml-auto rtl:mr-auto uppercase" type="submit">
                        Register
                    </x-core.buttons.primary-button>
                </div>
            </x-core.cards.form-card>
        </div>
    </div>

    <div id="guide-explanation" class="modal" data-animations="fadeInDown, fadeOutUp">
        <div class="modal-dialog w-1/2">
            <div class="modal-content">
                <div class="modal-header">
                    <h2 class="modal-title">What is The Guide</h2>
                    <button type="button" class="close la la-times" data-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-4">
                        The Guide is a NPC who will, as his name implies, Guide you through the game.
                        He starts off basic with killing creatures, and by the end of his quest line you will have a firm grasp
                        on the fundamentals of the game.
                    </p>
                    <p class="mb-4">After registration you will see the first guide quest modal popup in game with your first quest.</p>
                    <p class="mb-4">
                        During the course of the quest line, you will embark on a story of sorts as each Guide Quest is broken in three parts:
                    </p>
                    <ul class="list-disc ml-5 mb-4">
                        <li>Requirements</li>
                        <li>Story</li>
                        <li>Instructions</li>
                    </ul>
                    <p class="mb-4">
                        There are instructions for both Mobile and Desktop as the UI for each is slightly different,
                        all the mechanics are the same - just how you access specific things are different.
                    </p>
                    <p class="mb-4">
                        You can access The Guide any time by clicking the green button in the top bar after closing the guide modal.
                        Each quests teaches you a bit about the game, the lore and rewards you with gear and gold.
                    </p>
                    <p class="mb-4">
                        If you choose not to enable this, you can do so from the User settings page in game while your character is below level 10.
                        Guide quests will guide you through till level 1000 (there are 4000+ levels).
                    </p>
                    <p class="mb-4 text-red-500 font-bold italic">
                        Once enabled, Guide Quests cannot be shut off. The Modal that initiates the quests is not designed to be annoying (it won't always pop up, after the first time, it's up to you to open and initiate the next quest). Players will have access to their guide quests
                        under their quest log to refer to "simple" how to's. For detailed explanations, players can always refer to the <a href="/information/home">Help Docs</a>. Throughout the game there are various links
                        that reference the help docs for detailed info. The Guide does not go into in-depth explanation of mechanics, just the "how to access, what it does and how to complete the quest". We give you enough to complete the job, it's up to you to
                        reference the comprehensive help docs for more info or ask in chat or discord.
                    </p>
                </div>
                <div class="modal-footer">
                    <div class="flex ltr:ml-auto rtl:mr-auto">
                        <x-core.buttons.primary-button data-dismiss="modal">Close</x-core.buttons.primary-button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

