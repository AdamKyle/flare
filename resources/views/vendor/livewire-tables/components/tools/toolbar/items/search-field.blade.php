@aware(['isTailwind', 'isBootstrap'])

<div
  @class([
    'mb-md-0 input-group mb-3' => $isBootstrap,
    'rounded-md shadow-sm' => $isTailwind,
    'flex' => $isTailwind && ! $this->hasSearchIcon,
    'relative inline-flex flex-row' => $this->hasSearchIcon,
  ])
>
  @if ($this->hasSearchIcon)
    <x-livewire-tables::tools.toolbar.items.search.icon
      :searchIcon="$this->getSearchIcon"
      :searchIconClasses="$this->getSearchIconClasses"
      :searchIconOtherAttributes="$this->getSearchIconOtherAttributes"
    />
  @endif

  <x-livewire-tables::tools.toolbar.items.search.input />

  @if ($this->hasSearch)
    <x-livewire-tables::tools.toolbar.items.search.remove />
  @endif
</div>
