@extends(
    'layouts.information',
    [
        'pageTitle' => 'Unit',
    ]
)

@section('content')
    @include('admin.kingdoms.units.unit')
@endsection
