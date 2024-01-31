@php
    $editUrl = route('classes.edit', ['class' => $class->id]);
    $backUrl = route('classes.list');
    $buttons = 'true';

    if (is_null(auth()->user())) {
        $backUrl = '/information/races-and-classes';
    } elseif (
        !auth()
            ->user()
            ->hasRole('Admin')
    ) {
        $backUrl = '/information/races-and-classes';
    }
@endphp

<div class="w-full md:w-1/2 ml-auto m-auto">
    <x-core.cards.card-with-title title="{{ $class->name }}" css="mt-20 mb-10 w-full lg:w-1/2 m-auto"
        editUrl="{{ $editUrl }}" backUrl="{{ $backUrl }}" buttons="{{ $buttons }}">
        <div class="flex flex-wrap -mx-2 mb-8">
            <div class="w-full md:w-1/2 px-2 mb-4">
                <dl class="mb-4">
                    <dt>Strength Mofidfier</dt>
                    <dd>+ {{ $class->str_mod > 0 ? $class->str_mod : 0 }} pts.</dd>
                    <dt>Durability Modifier</dt>
                    <dd>+ {{ $class->dur_mod > 0 ? $class->dur_mod : 0 }} pts.</dd>
                    <dt>Dexterity Modifier</dt>
                    <dd>+ {{ $class->dex_mod > 0 ? $class->dex_mod : 0 }} pts.</dd>
                    <dt>Intelligence Modifier</dt>
                    <dd>+ {{ $class->int_mod > 0 ? $class->int_mod : 0 }} pts.</dd>
                    <dt>Charsima Modifier</dt>
                    <dd>+ {{ $class->chr_mod > 0 ? $class->chr_mod : 0 }} pts.</dd>
                    <dt>Focus Modifier</dt>
                    <dd>+ {{ $class->focus_mod > 0 ? $class->focus_mod : 0 }} pts.</dd>
                    <dt>Agility Modifier</dt>
                    <dd>+ {{ $class->aglity_modifier > 0 ? $class->aglity_modifier : 0 }} pts.</dd>
                    <dt>Accuracy Modifier</dt>
                    <dd>+ {{ $class->accuracy_mod * 100 }} %</dd>
                    <dt>Dodge Modifier</dt>
                    <dd>+ {{ $class->dodge_mod * 100 }} %</dd>
                    <dt>Looting Modifier</dt>
                    <dd>+ {{ $class->looting_mod * 100 }} %</dd>
                    <dt>Defense Modifier</dt>
                    <dd>+ {{ $class->defense_mod * 100 }} %</dd>
                </dl>
                <div class='border-b-2 border-b-gray-200 dark:border-b-gray-600 my-3'></div>
                <p class="my-2">
                    Damage Stat refers to the primary stat which your damage will come from.
                </p>
                <p class="my-2">
                    To Hit Stat refers to the stat in which is used in conjunction with your
                    accuracy and casting accuracy to determine if you can hit or if your damage spell hits.
                </p>
                <dl class="my-4">
                    <dt>Damage Stat</dt>
                    <dd>{{ $class->damage_stat }}</dd>
                    <dt>To Hit Stat</dt>
                    <dd>{{ $class->to_hit_stat }}</dd>
                </dl>
            </div>
            <div class="w-full md:w-1/2 px-2 mb-4">
                @if ($class->gameSkills->isNotEmpty())
                    <h5 class="mb-2">Class Skills</h5>
                    <ul>
                        @foreach ($class->gameSkills as $skill)
                            <li><a
                                    href="{{ route('info.page.skill', ['skill' => $skill->id]) }}">{{ $skill->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @endif
                <h5 class="mb-2 mt-2">Class Attack Bonus</h5>
                <p class="mb-4">
                    {{ $classBonus['description'] }}
                </p>
                <dl className="mt-4">
                    <dt>Type:</dt>
                    <dd>{{ $classBonus['type'] }}</dd>
                    <dt>Base Chance:</dt>
                    <dd>{{ $classBonus['base_chance'] * 100 }}%</dd>
                    <dt>Requirements:</dt>
                    <dd>{{ $classBonus['requires'] }}</dd>
                </dl>

                @if ($class->type()->isArcaneAlchemist())
                    @include('information.classes.partials.crafting.arcane-alchemist')
                @endif

                @if ($class->type()->isBlackSmith())
                    @include('information.classes.partials.crafting.blacksmith')
                @endif

                @if ($class->type()->isMerchant())
                    @include('information.classes.partials.crafting.merchant')
                @endif
            </div>
        </div>
    </x-core.cards.card-with-title>


    <x-core.cards.card-with-title title="Hints">
        <div class="prose dark:prose-invert">
            @if ($class->type()->isFighter())
                @include('information.classes.partials.fighter')
            @endif

            @if ($class->type()->isRanger())
                @include('information.classes.partials.ranger')
            @endif

            @if ($class->type()->isThief())
                @include('information.classes.partials.thief')
            @endif

            @if ($class->type()->isProphet())
                @include('information.classes.partials.prophet')
            @endif

            @if ($class->type()->isHeretic())
                @include('information.classes.partials.heretic')
            @endif

            @if ($class->type()->isVampire())
                @include('information.classes.partials.vampire')
            @endif

            @if ($class->type()->isBlackSmith())
                @include('information.classes.partials.blacksmith')
            @endif

            @if ($class->type()->isArcaneAlchemist())
                @include('information.classes.partials.arcane-alchemist')
            @endif

            @if ($class->type()->isPrisoner())
                @include('information.classes.partials.prisoner')
            @endif

            @if ($class->type()->isAlcoholic())
                @include('information.classes.partials.alcoholic')
            @endif

            @if ($class->type()->isMerchant())
                @include('information.classes.partials.merchant')
            @endif

            @if ($class->type()->isDancer())
                @include('information.classes.partials.dancer')
            @endif

            @if ($class->type()->isGunslinger())
                @include('information.classes.partials.gunslinger')
            @endif

            @if ($class->type()->isBookBinder())
                @include('information.classes.partials.book-binder')
            @endif

            @if ($class->type()->isCleric())
                @include('information.classes.partials.cleric')
            @endif
        </div>
    </x-core.cards.card-with-title>
</div>
