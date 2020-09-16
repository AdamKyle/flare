<div class="log-text pt-3">
    @if (!isset($levelLog['message']) && !isset($levelLog['messages']))
        @foreach($levelLog as $log)
            
            @if (isset($log['message']))
                <p class="text-center {{$log['is_monster'] ? 'monster-color' : 'character-color'}}">{{$log['message']}}</p>
            @else
                @foreach($log['messages'] as $key => $value)
                    
                    <p class="text-center {{$log['is_monster'] ? 'monster-color' : 'character-color'}}">{{$value[0]}}</p>
                @endforeach
            @endif
        @endforeach
    @else
        @if (isset($levelLog['message']))
            <p class="text-center {{$levelLog['is_monster'] ? 'monster-color' : 'character-color'}}">{{$levelLog['message']}}</p>
        @else
            @foreach($levelLog['messages'] as $key => $value)
                <p class="text-center {{$levelLog['is_monster'] ? 'monster-color' : 'character-color'}}">{{$value[0]}}</p>
            @endforeach
        @endif
    @endif
</div>
