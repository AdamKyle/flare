@extends('layouts.app')

@section('content')
    <div class="tw-w-full lg:tw-w-3/4 tw-m-auto">
        <x-core.page-title
          title="{{$character->name . '(LV: ' . number_format($character->level) . ')'}}"
          route="{{route('users.list')}}}}"
          link="Back"
          color="primary"
        ></x-core.page-title>

        @if ($character->user->is_banned)
            <h4 class="mt-3 ml-1">Banned Until: {{is_null($character->user->unbanned_at) ? 'For ever' : $character->user->unbanned_at->format('l jS \\of F Y h:i:s A')}} </h4>
            <x-core.cards.card>
                <p><strong>Banned Because: </strong> {{$character->user->banned_reason}}</p>

                @if (!is_null($character->user->un_ban_request))
                    <p><strong>Request: </strong> {{$character->user->un_ban_request}}</p>
                @endif
                <hr />
                <x-forms.button-with-form
                  form-route="{{route('unban.user', [
                                'user' => $character->user->id
                            ])}}"
                  form-id="{{$character->user->id}}-unban"
                  button-title="Unban"
                  class="btn btn-success float-right ml-2"
                />
                @if (!is_null($character->user->un_ban_request))
                    <button class="btn btn-danger float-right ml-2" data-toggle="modal" data-target="#are-you-sure-"{{$character->user->id}}>Ignore</button>

                    @include('admin.users.modals.are-you-sure', [
                        'character' => $character
                    ])
                    @endif
                    </div>
            </x-core.cards.card>
        @endif

        <x-core.cards.card css="tw-mb-5">
            <strong>Current Gold</strong>: {{number_format($character->gold)}}
        </x-core.cards.card>

        <x-core.cards.card>
            <div class="row mb-3">
                <div class="col-md-12">
                    <strong>Attack</strong>: {{number_format($character->getInformation()->getTotalWeaponDamage())}} / <strong>AC</strong>: {{number_format($character->getInformation()->buildDefence())}} / <strong>Heal For</strong>: {{number_format($character->getHeathInformation()->buildHealFor())}}
                    <hr />
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <strong>Race</strong>
                    <hr />
                    <dl>
                        <dt>name:</dt>
                        <dd>{{$character->race->name}}</dd>
                        <dt>Str Modifier:</dt>
                        <dd>{{$character->race->str_mod}} pts.</dd>
                        <dt>Dex Modifier:</dt>
                        <dd>{{$character->race->dex_mod}} pts.</dd>
                        <dt>Dur Modifier:</dt>
                        <dd>{{$character->race->dur_mod}} pts.</dd>
                        <dt>Int Modifier:</dt>
                        <dd>{{$character->race->int_mod}} pts.</dd>
                        <dt>Chr Modifier:</dt>
                        <dd>{{$character->race->chr_mod}} pts.</dd>
                    </dl>

                    <hr />
                    <strong>Class</strong>
                    <hr />
                    <dl>
                        <dt>Name:</dt>
                        <dd>{{$character->class->name}}</dd>
                        <dt>Str Modifier:</dt>
                        <dd>{{$character->class->str_mod}} pts.</dd>
                        <dt>Dex Modifier:</dt>
                        <dd>{{$character->class->dex_mod}} pts.</dd>
                        <dt>Dur Modifier:</dt>
                        <dd>{{$character->class->dur_mod}} pts.</dd>
                        <dt>Int Modifier:</dt>
                        <dd>{{$character->class->int_mod}} pts.</dd>
                        <dt>Chr Modifier:</dt>
                        <dd>{{$character->class->chr_mod}} pts.</dd>
                    </dl>
                </div>
                <div class="col-md-8">
                    <strong>Stats (With Modifiers)</strong>
                    <hr />
                    <dl>
                        <dt>Str:</dt>
                        <dd>{{number_format($character->getInformation()->statMod('str'))}}</dd>
                        <dt>Dex:</dt>
                        <dd>{{number_format($character->getInformation()->statMod('dex'))}}</dd>
                        <dt>Dur:</dt>
                        <dd>{{number_format($character->getInformation()->statMod('dur'))}}</dd>
                        <dt>Int:</dt>
                        <dd>{{number_format($character->getInformation()->statMod('int'))}}</dd>
                        <dt>Chr:</dt>
                        <dd>{{number_format($character->getInformation()->statMod('chr'))}}</dd>
                    </dl>
                    <hr />
                    <strong>Skills</strong>
                    <hr />
                    @foreach($character->skills as $skill)
                        <dl>
                            <dt><a href="{{route('skill.character.info', ['skill' => $skill->id])}}">{{$skill->name}}</a>:</dt>
                            <dd>
                                <div class="row">
                                    <div class="col-md-4">
                                        Level/Max: {{$skill->level}} / {{$skill->max_level}}
                                    </div>
                                    <div class="col-md-4">
                                        <div class="progress skill-training mb-2 text-center">
                                            <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$skill->xp}}" aria-valuemin="0" style="width: {{$skill->xp}}%;">{{$skill->xp}}</div>
                                        </div>
                                    </div>
                                    @if ((bool) $skill->can_train)
                                        <div class="col-md-4 text-left">
                                            @if ($skill->currently_training)
                                                <i class="fas fa-info-circle skill-info-icon text-info"
                                                   style="position:relative; top: -4px;"
                                                   data-toggle="tooltip" data-placement="top"
                                                   title="Xp % Towards: {{$skill->xp_towards * 100}}%"
                                                ></i>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </dd>
                        </dl>
                    @endforeach
                </div>
            </div>
        </x-core.cards.card>

        <h4 class="tw-mt-5">Currently Equipped</h4>
        <hr />

        @livewire('character.inventory.data-table', [
            'includeEquipped' => true,
            'character'       => $character,
        ])
    </div>
@endsection
