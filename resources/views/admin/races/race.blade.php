@extends('layouts.app')

@section('content')
<div class={{isset($customClass) ? $customClass . ' container' : 'container'}}>
    <div class="container justify-content-center">
        <div class="row page-titles">
            <div class="col-md-6 align-self-right">
                <h4 class="mt-2">{{$race->name}}</h4>
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
                            <dd>+ {{$race->str_mod}} pts.</dd>
                            <dt>Durability Modifier</dt>
                            <dd>+ {{$race->dur_mod}} pts.</dd>
                            <dt>Dexterity Modifier</dt>
                            <dd>+ {{$race->dex_mod}} pts.</dd>
                            <dt>Intelligence Modifier</dt>
                            <dd>+ {{$race->int_mod}} pts.</dd>
                            <dt>Charsima Modifier</dt>
                            <dd>+ {{$race->chr_mod}} pts.</dd>
                            <dt>Accuracy Modifier</dt>
                            <dd>+ {{$race->accuracy_mod * 100}} %</dd>
                            <dt>Dodge Modifier</dt>
                            <dd>+ {{$race->dodge_mod * 100}} %</dd>
                            <dt>Looting Modifier</dt>
                            <dd>+ {{$race->looting_mod * 100}} %</dd>
                        </dl>
                        @if (!is_null(auth()->user()))
                            @if (auth()->user()->hasRole('Admin'))
                                <a href="{{route('races.edit', [
                                    'race' => $race
                                ])}}" class="btn btn-primary mt-2">Edit</a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
