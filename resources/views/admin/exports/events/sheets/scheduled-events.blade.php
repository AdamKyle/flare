<table>
    <thead>
        <tr>
            <td>event_type</td>
            <td>raid_id</td>
            <td>start_date</td>
            <td>end_date</td>
            <td>description</td>
        </tr>
    </thead>
    <tbody>
    @foreach($events as $event)
        <tr>
            <td>{{$event->event_type}}</td>
            <td>{{!is_null($event->raid_id) ? $event->raid->name : ''}}</td>
            <td>{{$event->start_date}}</td>
            <td>{{$event->end_date}}</td>
            <td>{{$event->description}}</td>
        </tr>
    @endforeach
    </tbody>
</table>
