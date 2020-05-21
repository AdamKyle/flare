<div class="alert alert-success">
    Replacing this weapon will increase the attack by: {{$details['damage_adjustment']}}.
</div>
<h6>Item Details</h6>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Base Damage</th>
            <th>Type</th>
            <th>Position</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>{{$details['replaces_item']->name}}</th>
            <th>{{$details['replaces_item']->base_damage}}</th>
            <th>{{$details['replaces_item']->type}}</th>
            <th>{{$details['slot']->position}}</th>
        </tr>
    </tbody>
</table>
<hr />
<h6>Item Artifact</h6>
@if (is_null($details['replaces_item']->artifactProperty))
    <div class="alert alert-info">
        There is no artifact set to this item.
    </div>
@else
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Base Damage Mod</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <th>{{$details['replaces_item']->artifactProperty->name}}</th>
                <th>{{$details['replaces_item']->artifactProperty->base_damage_mod}}</th>
                <th>{{$details['replaces_item']->artifactProperty->description}}</th>
            </tr>
        </tbody>
    </table>
@endif

<hr />
<h6>Item Affixes</h6>
@if ($details['replaces_item']->itemAffixes->isEmpty())
    <div class="alert alert-info">
        There are no affixes on this item.
    </div>
@else
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Base Damage Mod</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
            @foreach($details['replaces_item']->itemAffixes as $affix)
                <tr>
                    <th>{{$affix->name}}</th>
                    <th>{{$affix->base_damage_mod}}</th>
                    <th>{{$affix->description}}</th>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif