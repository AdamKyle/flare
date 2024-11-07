@if ($class->type()->isFighter())
    @include('information.classes.partials.prefered-weapons.fighter')
@endif

@if ($class->type()->isHeretic())
    @include('information.classes.partials.prefered-weapons.heretic')
@endif

@if ($class->type()->isProphet())
    @include('information.classes.partials.prefered-weapons.prophet')
@endif

@if ($class->type()->isRanger())
    @include('information.classes.partials.prefered-weapons.ranger')
@endif

@if ($class->type()->isVampire())
    @include('information.classes.partials.prefered-weapons.vampire')
@endif

@if ($class->type()->isThief())
    @include('information.classes.partials.prefered-weapons.thief')
@endif

@if ($class->type()->isBlacksmith())
    @include('information.classes.partials.prefered-weapons.black-smith')
@endif

@if ($class->type()->isArcaneAlchemist())
    @include('information.classes.partials.prefered-weapons.arcane-alchemist')
@endif

@if ($class->type()->isPrisoner())
    @include('information.classes.partials.prefered-weapons.prisoner')
@endif

@if ($class->type()->isAlcoholic())
    @include('information.classes.partials.prefered-weapons.alcoholic')
@endif

@if ($class->type()->isGunslinger())
    @include('information.classes.partials.prefered-weapons.gunslinger')
@endif

@if ($class->type()->isDancer())
    @include('information.classes.partials.prefered-weapons.dancer')
@endif

@if ($class->type()->isBookBinder())
    @include('information.classes.partials.prefered-weapons.book-binder')
@endif

@if ($class->type()->isCleric())
    @include('information.classes.partials.prefered-weapons.cleric')
@endif

@if ($class->type()->isMerchant())
    @include('information.classes.partials.prefered-weapons.merchant')
@endif
