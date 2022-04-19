
<p>
    Below are the combination of the bonuses applied through the various holy stacks already applied.
    Stat increases will increase the stats on the item by the combined percentage. Whole Devouring Darkness will
    apply to your overall Devouring Darkness.
</p>

<p>
    Check the Holy Bonuses tab which is beside Devouring Light/Darkness tab on the character sheet for further bonuses
    that are applied to you only while wearing the holy items.
</p>

<x-tabs.pill-tabs-container>
    <x-tabs.tab tab="base-info" title="Base" selected="true" active="true" />
    <x-tabs.tab tab="stack-breakdown" title="Stack Break Down" selected="false" active="false" />
</x-tabs.pill-tabs-container>
<x-tabs.tab-content>
    <x-tabs.tab-content-section tab="base-info" active="true">
        <dl class="mt-4">
            <dt>Stat Increase %</dt>
            <dd class="text-success">{{$item->holy_stack_stat_bonus * 100}}%</dd>
            <dt>Devouring Darkness Bonus %</dt>
            <dd class="text-success">{{$item->holy_stack_devouring_darkness * 100}}%</dd>
        </dl>
    </x-tabs.tab-content-section>
    <x-tabs.tab-content-section tab="stack-breakdown" active="false">
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>
                        Stack #
                    </th>
                    <th>
                        Stat % increase
                    </th>
                    <th>
                        DD % increase
                    </th>
                </tr>
            </thead>
            <tbody>
                @php $stackNumber = 1; @endphp
                @foreach ($item->appliedHolyStacks as $stack)
                    <tr>
                        <td>{{$stackNumber}}</td>
                        <td>{{$stack->stat_increase_bonus * 100}}%</td>
                        <td>{{$stack->devouring_darkness_bonus * 100}}%</td>
                    </tr>
                    @php $stackNumber++; @endphp
                @endforeach
            </tbody>
        </table>
    </x-tabs.tab-content-section>
</x-tabs.tab-content>

