@extends('layouts.app')

@section('content')
        <x-core.page-title 
            title="{{$adventureLog->adventure->name}}"
            route="{{url()->previous()}}"
            link="Back"
            color="success"
        ></x-core.page-title>
        <div class="card">
            <div class="card-body">
                <p>{{$adventureLog->adventure->description}}</p>
                <hr />

                <dl>
                    <dt>Total Levels: </dt>
                    <dd>{{$adventureLog->adventure->levels}}</dd>
                    <dt>Last Completed Level:</dt>
                    <dd>{{$adventureLog->last_completed_level}}</dd>
                    <dt>Completed: </dt>
                    <dd>{!! $adventureLog->complete ? '<span class="text-success">Yes</span>' : '<span class="text-danger">No</span>' !!}</dd>
                </dl>
            </div>
        </div>

        @if (!is_null($adventureLog->rewards) && $adventureLog->complete)
            <div class="card mt-2">
            <div class="card-body">
                <h4 class="card-title">Rewards</h4>
                
                <dl>
                    <dt>Total XP: </dt>
                    <dd>{{$adventureLog->rewards['exp']}}</dd>
                    <dt>Skill XP Gained: </dt>
                    <dd>
                        @if (isset($adventureLog->rewards['skill']))
                            For Skill: {{$adventureLog->rewards['skill']['skill_name']}}, total: {{$adventureLog->rewards['skill']['exp']}}
                        @else
                            None.
                        @endif
                    </dd>
                    <dt>Total Gold: </dt>
                    <dd>{{$adventureLog->rewards['gold']}}</dd>
                    @if (!is_null($adventureLog->adventure->itemReward))
                        <dt>Adventure Reward Item<sup>*</sup>:</dt>
                        <dd>
                            <a href="{{route('items.item', [
                                'item' => $adventureLog->adventure->itemReward->id
                            ])}}">
                                <x-item-display-color :item="$adventureLog->adventure->itemReward" />
                            </a>
                        </dd>
                    @endif
                    <dt>Found Items: </dt>
                    @if (empty($adventureLog->rewards['items']))
                        <dd>No items were found.</dd>
                    @else
                        <dd>
                            <ul>
                                @foreach($adventureLog->rewards['items'] as $item)
                                    <li><a href="{{route('items.item', [
                                        'item' => $item['id']
                                    ])}}">{{$item['name']}}</a></li>
                                @endforeach
                            </ul>
                        </dd>
                    @endif
                </dl>

                @if (!is_null($adventureLog->adventure->itemReward))
                    <p style="font-size: 12px" class="text-muted"><sup>*</sup> This item has already been awarded to you upon a <strong>successful</strong> completion of the adventure, once.</p>
                @endif
            </div>
            <hr />
            <div class="clearfix">
                <form id="collect-reward" action="{{route('game.current.adventure.reward', [
                    'adventureLog' => $adventureLog
                ])}}" method="POST" style="display: none">
                    @csrf
                </form>

                <a class="float-left btn btn-primary mb-2 ml-2" href="#"
                onclick="event.preventDefault();
                                document.getElementById('collect-reward').submit();">
                    {{ __('Collect Rewards') }}
                </a>
            </div>
        </div>
    @endif

    <div class="justify-content-center">
        <x-adventure-logs :logs="$log" />
    </div>
@endsection
