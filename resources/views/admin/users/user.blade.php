@extends('layouts.app')

@section('content')
    <x-core.layout.info-container>
        <x-core.page.title
            title="{{$character->name}}"
            route="{{route('home')}}"
            color="success"
            link="Home"
        ></x-core.page.title>

        <x-core.cards.card>
            <div class="grid md:grid-cols-2 gap-3 mb-4">
                <div>
                    <dl>
                        <dt>Race</dt>
                        <dd>{{ $character->race->name }}</dd>
                        <dt>Class</dt>
                        <dd>{{ $character->class->name }}</dd>
                        <dt>Level</dt>
                        <dd>{{ $character->level }}</dd>
                    </dl>

                    <div
                        class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                    ></div>
                    <h3 class="text-sky-600 dark:text-sky-500">Currencies</h3>
                    <div
                        class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
                    ></div>
                    <dl>
                        <dt>Gold</dt>
                        <dd>{{ $character->gold }}</dd>
                        <dt>Gold Dust</dt>
                        <dd>{{ $character->gold_dust }}</dd>
                        <dt>Shards</dt>
                        <dd>{{ $character->shards }}</dd>
                        <dt>Copper Coins</dt>
                        <dd>{{ $character->copper_coins }}</dd>
                    </dl>
                </div>
                <div>
                    <dl>
                        <dt>(Raw) Str</dt>
                        <dd>{{ number_format($character->str) }}</dd>
                        <dt>(Raw) Dex</dt>
                        <dd>{{ number_format($character->dex) }}</dd>
                        <dt>(Raw) Int</dt>
                        <dd>{{ number_format($character->int) }}</dd>
                        <dt>(Raw) Agi</dt>
                        <dd>{{ number_format($character->agi) }}</dd>
                        <dt>(Raw) Chr</dt>
                        <dd>{{ number_format($character->chr) }}</dd>
                        <dt>(Raw) Focus</dt>
                        <dd>{{ number_format($character->focus) }}</dd>
                    </dl>
                </div>
            </div>

            <div
                class="border-b-2 border-b-gray-300 dark:border-b-gray-600 my-3"
            ></div>
            <h3 class="text-sky-600 dark:text-sky-500">
                Administrator Actions_2
            </h3>
            <div
                class="accordion border border-gray-300 dark:border-gray-900 rounded-xl mt-5"
            >
                <h5
                    class="border-t border-gray-300 dark:border-gray-900 p-5"
                    data-toggle="collapse"
                    data-target="#accordion-2"
                >
                    Force Name Change
                    <span
                        class="collapse-indicator la la-arrow-circle-down"
                    ></span>
                </h5>
                <div id="accordion-2" class="collapse">
                    <div class="p-5 pt-0">
                        <x-core.forms.regular-form
                            method="POST"
                            action="{{ route('user.force.name.change', ['user' => $character->user]) }}"
                        >
                            @csrf

                            <x-core.buttons.primary-button
                                css="ltr:ml-auto rtl:mr-auto"
                                type="submit"
                            >
                                Force user to change their name
                            </x-core.buttons.primary-button>
                        </x-core.forms.regular-form>
                    </div>
                </div>
                <h5
                    class="border-t border-gray-300 dark:border-gray-900 p-5"
                    data-toggle="collapse"
                    data-target="#accordion-3"
                >
                    Silence Character
                    <span
                        class="collapse-indicator la la-arrow-circle-down"
                    ></span>
                </h5>
                <div id="accordion-3" class="collapse">
                    <div class="p-5 pt-0">
                        <x-core.forms.regular-form
                            method="POST"
                            action="{{ route('user.silence', ['user' => $character->user]) }}"
                        >
                            @csrf

                            <div class="mb-5">
                                <label
                                    for="silence-options"
                                    class="label block mb-2"
                                >
                                    Silence Options
                                </label>
                                <select
                                    class="form-control"
                                    id="silence-options"
                                    name="silence_for"
                                >
                                    <option>Please select</option>
                                    <option value="5">5 Minutes</option>
                                    <option value="10">10 Minutes</option>
                                    <option value="30">30 Minutes</option>
                                </select>
                            </div>

                            <div class="flex">
                                <x-core.buttons.primary-button
                                    css="ltr:ml-auto rtl:mr-auto"
                                    type="submit"
                                >
                                    Silence User
                                </x-core.buttons.primary-button>
                            </div>
                        </x-core.forms.regular-form>
                    </div>
                </div>
                <h5
                    class="border-t border-gray-300 dark:border-gray-900 p-5"
                    data-toggle="collapse"
                    data-target="#accordion-4"
                >
                    Ban Character
                    <span
                        class="collapse-indicator la la-arrow-circle-down"
                    ></span>
                </h5>
                <div id="accordion-4" class="collapse">
                    <div class="p-5 pt-0">
                        @if ($character->user->ignored_unban_request)
                            <div
                                class="mb-4 mt-4 text-red-600 dark:text-red-500"
                            >
                                You have chosen to ignore this users request to
                                be unbanned. You may however, unban the
                                character.
                            </div>

                            <div class="mb-4 mt-4">
                                <strong>Reason They are banned:</strong>
                                {{ $character->user->banned_reason }}
                            </div>

                            <div class="mb-4 mt-4">
                                <strong>Character Request:</strong>
                                {{ $character->user->un_ban_request }}
                            </div>

                            <x-core.forms.regular-form
                                method="POST"
                                action="{{ route('unban.user', ['user' => $character->user]) }}"
                            >
                                @csrf
                                <div class="flex">
                                    <x-core.buttons.success-button
                                        type="submit"
                                    >
                                        Unban User
                                    </x-core.buttons.success-button>
                                </div>
                            </x-core.forms.regular-form>
                        @elseif (! is_null($character->user->un_ban_request))
                            <div class="mb-4 mt-4">
                                <strong>Reason They are banned:</strong>
                                {{ $character->user->banned_reason }}
                            </div>

                            <div class="mb-4 mt-4">
                                <strong>Character Request:</strong>
                                {{ $character->user->un_ban_request }}
                            </div>

                            <h4 class="mb-5 mt-5">Unban Character</h4>

                            <x-core.forms.regular-form
                                method="POST"
                                action="{{ route('unban.user', ['user' => $character->user]) }}"
                            >
                                @csrf
                                <div class="flex">
                                    <x-core.buttons.success-button
                                        type="submit"
                                    >
                                        Unban User
                                    </x-core.buttons.success-button>
                                </div>
                            </x-core.forms.regular-form>

                            <h4 class="mb-5 mt-5">Or, Ignore Request</h4>

                            <x-core.forms.regular-form
                                method="POST"
                                action="{{ route('user.ignore.unban.request', ['user' => $character->user]) }}"
                            >
                                @csrf
                                <div class="flex">
                                    <x-core.buttons.primary-button
                                        type="submit"
                                    >
                                        Ignore Request
                                    </x-core.buttons.primary-button>
                                </div>
                            </x-core.forms.regular-form>
                        @elseif ($character->user->is_banned)
                            <div class="mb-4 mt-4">
                                <strong>Reason They are banned:</strong>
                                {{ $character->user->banned_reason }}
                            </div>

                            <x-core.forms.regular-form
                                method="POST"
                                action="{{ route('unban.user', ['user' => $character->user]) }}"
                            >
                                @csrf
                                <div class="flex">
                                    <x-core.buttons.success-button
                                        type="submit"
                                    >
                                        Unban User
                                    </x-core.buttons.success-button>
                                </div>
                            </x-core.forms.regular-form>
                        @else
                            <x-core.forms.regular-form
                                method="POST"
                                action="{{ route('ban.user', ['user' => $character->user]) }}"
                            >
                                @csrf

                                <div class="mb-5">
                                    <label
                                        for="ban-length-options"
                                        class="label block mb-2"
                                    >
                                        Ban Options
                                    </label>
                                    <select
                                        class="form-control"
                                        id="ban-length-options"
                                        name="for"
                                    >
                                        <option>Please select</option>
                                        <option value="one-day">1 Day</option>
                                        <option value="one-week">1 Week</option>
                                        <option value="perm">For ever</option>
                                    </select>
                                </div>

                                <div class="mb-5">
                                    <label
                                        for="ban-reason"
                                        class="label block mb-2"
                                    >
                                        Reason
                                    </label>
                                    <textarea
                                        class="form-control"
                                        id="ban-reason"
                                        name="reason"
                                    ></textarea>
                                </div>

                                <div class="flex">
                                    <x-core.buttons.primary-button
                                        css="ltr:ml-auto rtl:mr-auto"
                                        type="submit"
                                    >
                                        Ban User
                                    </x-core.buttons.primary-button>
                                </div>
                            </x-core.forms.regular-form>
                        @endif
                    </div>
                </div>
            </div>
        </x-core.cards.card>
    </x-core.layout.info-container>
@endsection
