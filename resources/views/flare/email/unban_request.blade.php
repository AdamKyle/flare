@extends('flare.email.core_email', [
    'title'          => 'Request to be unbanned',
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#637381">
            Hello Administrator, {{$user->character->name}} is requesting to be unbanned.
        </mj-text>

        <mj-text color="#637381">
            Original Reason:
        </mj-text>

        <mj-text color="#637381">
            {{$user->banned_reason}}
        </mj-text>

        <mj-text color="#637381">
            Request:
        </mj-text>

        <mj-text color="#637381">
            {{$user->un_ban_request}}
        </mj-text>

        <mj-button background-color="#21A52C" align="center" color="#ffffff" font-size="17px" font-weight="bold" href="{{route('login')}}" width="300px">
            Login
        </mj-button>

    </mj-column>
@endsection
