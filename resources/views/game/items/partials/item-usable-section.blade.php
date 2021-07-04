<x-cards.card-with-title title="Usable Item">
    <div class="alert alert-info mb-3 mt-2">
        <p>This item is usable, what that means is either via chat or via the inventory (action drop down)
            You can "use" the item.</p>

        <p>
            Upon using this item, the effects below will take place for the allotted time (in minutes). You can see what affects are applied
            by clicking on the tab beside "Character Info" called "Boons". You can cancel a boon at any time by clicking it and canceling it.
        </p>
        <p>
            These items cannot be sold via the market bord and cannot be sold via the shop. They can only be crafted via <a href="#">Alchemy</a>.
        </p>
    </div>

    @if ($item->damages_kingdoms)
        <div class="alert alert-warning mb-3">
            </p>
                This is a single use item that can only be used when attacking kingdoms. The way this works is you move to a kingdom you want to attack.
                Then you click <strong>Attack Kingdom</strong>. from here, instead of selecting a kingdom to attack with, you can select to use an item, from there you can pick
                the item to use.
            <p>

            <p>
                Upon use, the damage shown below will be whats done to all aspects of the kingdom. Buildings, morale, units - all reduced - at once.
            </p>

            <p>
                You cannot use these items to take a kingdom. Even if you decimate the kingdom, you must move in with a <strong>settler</strong> to take the kingdom.
            </p>
        </div>
        <dl>
            <dt>Damages Kingdom For:</dt>
            <dd>{{$item->kingdom_damage * 100}}%</dd>
        </dl>
    @else
        <dl>
            <dt>Lasts For: </dt>
            <dd>{{$item->lasts_for}} Minutes</dd>

            @if ($item->stat_increase)
                <dt>Increases all core stats by: </dt>
                <dd>{{$item->increase_stat_by * 100}}%</dd>
            @endif
            @if (!is_null($item->affects_skill_type))
                <dt>Skills Affected: </dt>
                <dd>{{empty($skills) ? 'None' : implode(', ', $skills)}}</dd>
                <dt>Skills Bonus: </dt>
                <dd>{{$item->increase_skill_bonus_by * 100}}%</dd>
                <dt>Skills Affected: </dt>
                <dd>{{$item->increase_skill_training_bonus_by * 100}}%</dd>
            @endif
        </dl>
    @endif
</x-cards.card-with-title>
