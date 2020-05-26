<h6>Item Details</h6>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>Name</th>
            <th>Base Damage</th>
            <th>Type</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <th>{{$item->name}}</th>
            <th>{{$item->base_damage}}</th>
            <th>{{$item->type}}</th>
        </tr>
    </tbody>
</table>
<hr />
<h6>Item Artifact</h6>
@if (is_null($item->artifactProperty))
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
                <th>{{$item->artifactProperty->name}}</th>
                <th>{{$item->artifactProperty->base_damage_mod}}</th>
                <th>{{$item->artifactProperty->description}}</th>
            </tr>
        </tbody>
    </table>
@endif

<hr />
<h6>Item Affixes</h6>
@if (is_null($item->itemPrefix) && is_null($item->itemSuffix))
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
            @if (!is_null($item->itemPrefix))
                <tr>
                    <th>{{$item->itemPrefix->name}}</th>
                    <th>{{$item->itemPrefix->base_damage_mod}}</th>
                    <th>{{$item->itemPrefix->description}}</th>
                </tr>
            @endif
            @if (!is_null($item->itemSuffix))
                <tr>
                    <th>{{$item->itemSuffix->name}}</th>
                    <th>{{$item->itemSuffix->base_damage_mod}}</th>
                    <th>{{$item->itemSuffix->description}}</th>
                </tr>
            @endif
        </tbody>
    </table>
@endif