@extends('layouts.information', [
    'pageTitle' => 'Class'
])

@section('content')
    <div class="pt-5">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$class->name}}</h4>
            </div>
            <div class="col-md-6 align-self-right">
                <a href="{{url()->previous()}}" class="btn btn-primary float-right ml-2">Back</a>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <dl>
                            <dt>Strength Mofidfier</dt>
                            <dd>+ {{$class->str_mod}} pts.</dd>
                            <dt>Durability Modifier</dt>
                            <dd>+ {{$class->dur_mod}} pts.</dd>
                            <dt>Dexterity Modifier</dt>
                            <dd>+ {{$class->dex_mod}} pts.</dd>
                            <dt>Intelligence Modifier</dt>
                            <dd>+ {{$class->int_mod}} pts.</dd>
                            <dt>Charsima Modifier</dt>
                            <dd>+ {{$class->chr_mod}} pts.</dd>
                            <dt>Accuracy Modifier</dt>
                            <dd>+ {{$class->accuracy_mod * 100}} %</dd>
                            <dt>Dodge Modifier</dt>
                            <dd>+ {{$class->dodge_mod * 100}} %</dd>
                            <dt>Looting Modifier</dt>
                            <dd>+ {{$class->looting_mod * 100}} %</dd>
                            <dt>Defense Modifier</dt>
                            <dd>+ {{$class->deffense_mod * 100}} %</dd>
                        </dl>
                        @if ($class->gameSkills->isNotEmpty())
                            <hr />
                            <h2>Class Skills</h2>
                            <ul>
                                @foreach ($class->gameSkills as $skill)
                                    <li><a href="{{route('info.page.skill', ['skill' => $skill->id])}}">{{$skill->name}}</a></li>
                                @endforeach
                            </ul>
                        @endif
                        @if (!is_null(auth()->user()))
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('classes.edit', [
                                    'class' => $class
                                ])}}" class="btn btn-primary mt-2">Edit</a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
