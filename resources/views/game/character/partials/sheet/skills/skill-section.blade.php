<dl>
    <dt>
        <a href="{{route('skill.character.info', ['skill' => $skill->id])}}" class="{{$skill->is_locked ? 'text-danger' : ''}}">
            {{$skill->name}}

            @if ($skill->is_locked)
                <i class="fas fa-lock"></i>
            @endif
        </a>:
    </dt>
    <dd>
        <div class="row">
            <div class="col-xs-12 col-sm-12 col-md-3">
                Level: {{$skill->level}} / {{$skill->max_level}}
            </div>
            <div class="col-xs-12 col-sm-12 col-md-3">
                XP: {{$skill->xp}} / {{$skill->xp_max}}
            </div>
            <div class="col-xs-12 col-sm-12 col-md-2 mb-3">
                <div class="progress skill-training mb-2 text-center">
                    <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$skill->xp}}" aria-valuemin="0" style="width: {{($skill->xp/$skill->xp_max) * 100}}%;"></div>
                </div>
            </div>
            @if ($skill->can_train)
                <div class="col-md-4">
                    @if (!auth()->user()->hasRole('Admin'))
                        <button
                            class="btn btn-{{$skill->currently_training ? 'success' : 'primary'}} btn-sm mb-2 train-skill-btn"
                            data-toggle="modal"
                            data-target="#skill-train-{{$skill->id}}"
                            {{!$character->can_adventure ? 'disabled' : ''}}
                        >
                            Train

                            @if ($skill->currently_training)
                                <i class="ml-2 fas fa-check"></i>
                            @endif
                        </button>
                    @endif

                    @if ($skill->currently_training)
                        <x-forms.button-with-form
                            class="btn btn-danger btn-sm mb-2 train-skill-btn"
                            formRoute="{{ route('cancel.train.skill', [
                                        'skill' => $skill->id
                                    ]) }}"
                            buttonTitle="Stop"
                            formId="cancel-skill-train-form"
                        >

                        </x-forms.button-with-form>

                        <i class="ml-2 fas fa-info-circle skill-info-icon text-info"
                           data-toggle="tooltip" data-placement="top"
                           title="Xp % Towards: {{$skill->xp_towards * 100}}%"
                        ></i>
                    @endif

                    @include('game.character.partials.sheet.admin.skill-train-buttons')
                    @include('game.character.modals.skill-train-modal', ['skill' => $skill, 'character' => $character])
                </div>
            @endif
        </div>
    </dd>
</dl>
