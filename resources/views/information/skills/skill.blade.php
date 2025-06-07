@extends(
    'layouts.information',
    [
        'pageTitle' => 'Skill',
    ]
)

@section('content')
    @include(
        'admin.skills.partials.skill-info',
        [
            'skill' => $skill,
        ]
    )
@endsection
