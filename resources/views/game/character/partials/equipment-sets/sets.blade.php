<div class="mt-3">
    <x-tabs.pill-tabs-container>
        <x-tabs.tab tab="set-1" title="Set One" selected="true" active="true" icon="fas fa-exclamation-triangle"/>
        <x-tabs.tab tab="set-2" title="Set Two" selected="false" active="false" />
        <x-tabs.tab tab="set-3" title="Set Three" selected="false" active="false" />
        <x-tabs.tab tab="set-4" title="Set Four" selected="false" active="false" />
        <x-tabs.tab tab="set-5" title="Set Five" selected="false" active="false" />
        <x-tabs.tab tab="set-6" title="Set Six" selected="true" active="false" />
        <x-tabs.tab tab="set-7" title="Set Seven" selected="false" active="false" />
        <x-tabs.tab tab="set-8" title="Set Eight" selected="false" active="false" />
        <x-tabs.tab tab="set-9" title="Set Nine" selected="false" active="false" />
        <x-tabs.tab tab="set-10" title="Set Ten" selected="false" active="false" />

    </x-tabs.pill-tabs-container>
    <x-tabs.tab-content>
        <x-tabs.tab-content-section tab="set-1" active="true">
            <div class="alert alert-warning mt-2 mb-3">
                <p>
                    This set cannot be equipped due to the items in it.
                    Remember a set contains: 2 weapons (or 1 weapon and a shield) one of each armour
                    piece (excluding shield if you are dual wielding), 2 rings, 2 spells and 2 artifacts.
                </p>
                <p>Sets may be incomplete, in that case we will just replace the appropriate gear.</p>
                <p>You may also treat sets as a stash tab, which seems to be what you are doing here - they just cant be equipped automatically.</p>
            </div>
            <button class="btn btn-primary btn-sm">re-name set</button>
            <hr />
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-2" active="false">
            <button class="btn btn-primary btn-sm">re-name set</button>
            <hr />
            Second Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-3" active="false">
            Third Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-4" active="false">
            Fourth Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-5" active="false">
            Fifth Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-6" active="false">
            First Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-7" active="false">
            Second Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-8" active="false">
            Third Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-9" active="false">
            Fourth Set
        </x-tabs.tab-content-section>
        <x-tabs.tab-content-section tab="set-10" active="false">
            Fifth Set
        </x-tabs.tab-content-section>
    </x-tabs.tab-content>
</div>
