@extends('flare.email.core_email', [
    'title'          => $genericSubject,
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        @if ($user->hasRole('Admin'))
            <mj-text color="#637381">
                Hello Administrator,
            </mj-text>
        @else
            <mj-text color="#637381">
                Hello {{$user->character->name}},
            </mj-text>
        @endif

        <mj-text color="#637381">
            {{$genericMessage}}
        </mj-text>

        @if (!$dontShowLogin)
            <mj-button background-color="#21A52C" align="center" color="#ffffff" font-size="17px" font-weight="bold" href="{{route('login')}}" width="300px">
                Login
            </mj-button>
        @endif

    </mj-column>
@endsection
