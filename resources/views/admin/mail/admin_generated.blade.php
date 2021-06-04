@extends('flare.email.core_email', [
    'title'          => 'Admin Account Created',
    'showBottomText' => false,
])

@section('content')
    <mj-column width="400px">

        <mj-text color="#637381">
            Hello. Your admin account has been created. Please reset your password before logging in.
        </mj-text>

        <mj-button background-color="#21A52C" align="center" color="#ffffff" font-size="17px" font-weight="bold" href="{{route('password.reset', $token)}}" width="300px">
            Reset password
        </mj-button>

    </mj-column>
@endsection
