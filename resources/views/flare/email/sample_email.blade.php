@extends('flare.email.core_email', [
    'title'          => $title,
    'showBottomText' => true,
])

@section('content')
    <mj-column width="400px">

        <mj-text font-style="italic"
                font-size="20px"
                font-family="Helvetica Neue"
                color="#626262">
            My Awesome Text
        </mj-text>

        <mj-text color="#525252">
            Lorem ipsum dolor sit amet, consectetur adipiscing elit. Proin rutrum enim eget magna efficitur, eu semper augue semper. Aliquam erat volutpat. Cras id dui lectus. Vestibulum sed finibus lectus, sit amet suscipit nibh. Proin nec commodo purus. Sed eget nulla elit. Nulla aliquet mollis faucibus.
        </mj-text>

        <mj-button background-color="#F45E43"
                href="#">
            Learn more
        </mj-button>

    </mj-column>
@endsection