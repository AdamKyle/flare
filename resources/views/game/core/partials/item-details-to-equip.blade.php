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
@if ($item->itemAffixes->isEmpty())
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
            @foreach($item->itemAffixes as $affix)
                <tr>
                    <th>{{$affix->name}}</th>
                    <th>{{$affix->base_damage_mod}}</th>
                    <th>{{$affix->description}}</th>
                </tr>
            @endforeach
        </tbody>
    </table>
@endif