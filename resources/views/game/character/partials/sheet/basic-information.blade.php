<dl>
    <dt>Character Name:</dt>
    <dd>{{$character->name}}</dd>
    <dt>Character Race:</dt>
    <dd>{{$character->race->name}}</dd>
    <dt>Character Class:</dt>
    <dd>{{$character->class->name}}</dd>
    <dt>Character Level:</dt>
    <dd>{{$character->level}}</dd>
    <dt>Character XP:</dt>
    <dd>
        <div class="progress skill-training mb-2">
            <div class="progress-bar skill-bar" role="progressbar" aria-valuenow="{{$character->xp}}" aria-valuemin="0" style="width: {{$character->xp}}%;">{{$character->xp}}</div>
        </div>
    </dd>
</dl>
