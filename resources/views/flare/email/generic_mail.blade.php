@extends('flare.email.core_email', [
    'title'          => $genericSubject,
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        @if ($user->hasRole('Admin'))
            <mj-text color="#dedede">
                Hello Administrator,
            </mj-text>
        @else
            <mj-text color="#dedede">
                Hello {{$user->character->name}},
            </mj-text>
        @endif

        <mj-text color="#dedede">
            {{$genericMessage}}
        </mj-text>

        @if (!$dontShowLogin)
            <mj-button background-color="#388a2d"
                    href="{{route('login')}}">
                Login!
            </mj-button>
        @endif

    </mj-column>
@endsection