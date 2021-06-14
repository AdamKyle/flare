@foreach ($data as $index => $battleData)
    @if (is_array($battleData))
        @if (!isset($battleData['monster']))
            @continue
        @endif

        @php
            $isMonster  = $battleData['is_monster'];
        @endphp

        @if (isset($battleData['message']))
            <p class="text-center {{$isMonster ? 'monster-color' : 'character-color'}}">{{$battleData['message']}}</p>
        @endif

        @if (isset($battleData['messages']))
            @foreach($battleData['messages'] as $key => $value)
                <p class="text-center {{$isMonster ? 'monster-color' : 'character-color'}}">{{$value[0]}}</p>
            @endforeach
        @endif
    @endif
@endforeach
