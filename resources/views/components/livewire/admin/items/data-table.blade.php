<div class="row justify-content-center">
    <div class="col-md-12">
      <x-core.cards.card css="tw-mt-5 tw-w-full tw-m-auto">
        <div class="row pb-2">
            <x-data-tables.per-page wire:model="perPage">
                @if ($this->type !== 'alchemy')
                    <div class="btn-group">
                        <button type="button" class="ml-2 btn btn-sm btn-primary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Type
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#" wire:click="setType('weapon')">Weapon</a>
                            <a class="dropdown-item" href="#" wire:click="setType('bow')">Bow</a>
                            <a class="dropdown-item" href="#" wire:click="setType('body')">Body</a>
                            <a class="dropdown-item" href="#" wire:click="setType('shield')">Shield</a>
                            <a class="dropdown-item" href="#" wire:click="setType('feet')">Feet</a>
                            <a class="dropdown-item" href="#" wire:click="setType('leggings')">Leggings</a>
                            <a class="dropdown-item" href="#" wire:click="setType('sleeves')">Sleeves</a>
                            <a class="dropdown-item" href="#" wire:click="setType('helmet')">Helmet</a>
                            <a class="dropdown-item" href="#" wire:click="setType('gloves')">Gloves</a>
                            <a class="dropdown-item" href="#" wire:click="setType('spell-healing')">Spells Healing</a>
                            <a class="dropdown-item" href="#" wire:click="setType('spell-damage')">Spells Damage</a>
                            <a class="dropdown-item" href="#" wire:click="setType('ring')">Ring</a>
                            <a class="dropdown-item" href="#" wire:click="setType('artifact')">Artifact</a>
                            @auth
                              @if (auth()->user()->hasRole('Admin'))
                                <a class="dropdown-item" href="#" wire:click="setType('alchemy')">Alchemy</a>
                              <a class="dropdown-item" href="#" wire:click="setType('quest')">Quest</a>
                              @endif
                            @endauth
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="#" href="#" wire:click="setType('reset')">Reset</a>
                        </div>
                    </div>
                @endif
            </x-data-tables.per-page>
            <x-data-tables.search wire:model="search" />
        </div>

        @empty ($selected)
        @else
            @guest
            @else
                @if (auth()->user()->hasRole('Admin'))
                    <div class="float-right pb-2">
                        <x-forms.button-with-form
                            form-route="{{route('items.delete.all')}}"
                            form-id="{{'delete-items-in-bulk'}}"
                            button-title="Delete All"
                            class="btn btn-danger btn-sm"
                        >
                            @forelse( $selected as $item)
                                <input type="hidden" name="items[]" value="{{$item}}" />
                            @empty
                                <input type="hidden" name="items[]" value="" />
                            @endforelse

                        </x-forms.button-with-form>
                    </div>
                @else
                    <div class="float-right pb-2">
                        <x-forms.button-with-form
                            formRoute="{{route('game.shop.buy.bulk', ['character' => $character->id])}}"
                            formId="{{'shop-buy-form-item-in-bulk'}}"
                            buttonTitle="Buy All"
                            class="btn btn-primary btn-sm"
                        >
                            @forelse( $selected as $item)
                                <input type="hidden" name="items[]" value="{{$item}}" />
                            @empty
                                <input type="hidden" name="items[]" value="" />
                            @endforelse

                        </x-forms.button-with-form>
                    </div>
                @endif
            @endguest
        @endempty

        <x-data-tables.table :collection="$items">
            <x-data-tables.header>
                @guest
                @elseif (!is_null($character))
                    <x-data-tables.header-row>
                        <input type="checkbox" wire:model="pageSelected"/>
                    </x-data-tables.header-row>
                @endguest

                <x-data-tables.header-row
                    wire:click.prevent="sortBy('name')"
                    header-text="Name"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="name"
                />

                <x-data-tables.header-row
                    wire:click.prevent="sortBy('type')"
                    header-text="Type"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="type"
                />

                <x-data-tables.header-row
                    wire:click.prevent="sortBy('base_damage')"
                    header-text="Base Damage"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="base_damage"
                />

                <x-data-tables.header-row
                    wire:click.prevent="sortBy('base_ac')"
                    header-text="Base AC"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="base_ac"
                />

                <x-data-tables.header-row
                    wire:click.prevent="sortBy('base_healing')"
                    header-text="Base Healing"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="base_ac"
                />

                <x-data-tables.header-row
                    wire:click.prevent="sortBy('cost')"
                    header-text="Cost"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="cost"
                />

                @if ($showOtherCurrencyCost)
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('gold_dust_cost')"
                        header-text="Gold Dust Cost"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="gold_dust_cost"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('shards_cost')"
                        header-text="Shards Cost"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="shards_cost"
                    />
                @endif

                @if ($showOtherCurrencyCost)
                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('gold_dust_cost')"
                        header-text="Gold Dust Cost"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="gold_dust_cost"
                    />

                    <x-data-tables.header-row
                        wire:click.prevent="sortBy('shards_cost')"
                        header-text="Shards Cost"
                        sort-by="{{$sortBy}}"
                        sort-field="{{$sortField}}"
                        field="shards_cost"
                    />
                @endif

                  <x-data-tables.header-row
                    wire:click.prevent="sortBy('skill_level_required')"
                    header-text="Crafting Skill Level Required"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="skill_level_required"
                  />

                  <x-data-tables.header-row
                    wire:click.prevent="sortBy('skill_level_trivial')"
                    header-text="Crafting Skill Level Trivial"
                    sort-by="{{$sortBy}}"
                    sort-field="{{$sortField}}"
                    field="skill_level_trivial"
                  />

                @guest
                @elseif (auth()->user()->hasRole('Admin'))
                    <x-data-tables.header-row>
                        Actions
                    </x-data-tables.header-row>
                @endGuest
            </x-data-tables.header>
            <x-data-tables.body>
                @if ($pageSelected)
                    <tr>
                        <td colspan="8">
                            @unless($selectAll)
                                <div>
                                    <span>You have selected <strong>{{$items->count()}}</strong> items of <strong>{{$items->total()}}</strong>. Would you like to select all?</span>
                                    <button class="btn btn-link" wire:click="selectAll">Select all</button>
                                </div>
                            @else
                                <span>You are currently selecting all <strong>{{$items->total()}}</strong> items.</span>
                            @endunless
                        </td>
                    </tr>
                @endif
                @forelse($items as $item)
                    <tr wire:key="items-table-{{$item->id}}">
                        @guest
                        @elseif (!is_null($character))
                            <td>
                                <input type="checkbox" wire:model="selected" value="{{$item->id}}"/>
                            </td>
                        @endguest
                        @guest
                            <td>
                                <a href="{{route('info.page.item', [
                                    'item' => $item->id
                                ])}}">
                                    <x-item-display-color :item="$item" />
                                </a>
                            </td>
                        @else
                            @if ($previousUrlIsInfo = strpos(url()->previous(), 'information') !== false)
                                    <td>
                                        <a href="{{route('info.page.item', [
                                            'item' => $item->id
                                        ])}}">
                                            <x-item-display-color :item="$item" />
                                        </a>
                                    </td>
                            @else
                                @if (auth()->user()->hasRole('Admin'))
                                    <td>
                                        <a href="{{route('items.item', [
                                            'item' => $item->id
                                        ])}}">
                                            <x-item-display-color :item="$item" />
                                        </a>
                                    </td>
                                @else
                                    <td>
                                        <a href="{{route('game.items.item', [
                                        'item' => $item->id
                                    ])}}">
                                            <x-item-display-color :item="$item" />
                                        </a>
                                    </td>
                                @endif
                            @endif
                        @endguest


                        <td>{{$item->type}}</td>
                        <td>{{is_null($item->base_damage) ? 0 : $item->base_damage}}</td>
                        <td>{{is_null($item->base_ac) ? 0 : $item->base_ac}}</td>
                        <td>{{is_null($item->base_healing) ? 0 : $item->base_healing}}</td>
                        <td>{{is_null($item->cost) ? 0 : number_format($item->cost)}}</td>

                        @if ($showOtherCurrencyCost)
                            <td>{{is_null($item->gold_dust_cost) ? 0 : number_format($item->gold_dust_cost)}}</td>
                            <td>{{is_null($item->shards_cost) ? 0 : number_format($item->shards_cost)}}</td>
                        @endif

                          <td>{{$item->skill_level_required}}</td>
                          <td>{{$item->skill_level_trivial}}</td>

                        @guest
                        @else
                            @if(auth()->user()->hasRole('Admin'))
                              <td>
                                <a href="{{route('items.edit', [
                                        'item' => $item->id
                                    ])}}" class="btn btn-sm btn-primary mb-2">
                                    Edit
                                </a>

                                <x-forms.button-with-form
                                    formRoute="{{route('items.delete', [
                                            'item' => $item->id
                                        ])}}"
                                    formId="{{'delete-item-'.$item->id}}"
                                    buttonTitle="Delete"
                                    class="btn btn-danger btn-sm"
                                />
                              </td>
                            @elseif (!is_null($character))
                              <td>
                                <x-forms.button-with-form
                                  form-route="{{route('game.shop.buy.item', ['character' => $character->id])}}"
                                  form-id="{{'shop-buy-form-item-'.$item->id}}"
                                  button-title="Buy"
                                  class="btn btn-primary btn-sm"
                                >
                                  <input type="hidden" name="item_id" value={{$item->id}} />
                                </x-forms.button-with-form>

                                <a href="{{route('game.shop.compare.item', [
                                          'character' => $character->id,
                                          'item_id'   => $item->id,
                                          'item_type' => $item->type,
                                      ])}}" class="btn btn-primary btn-sm" id="{{'compare-item-' . $item->id}}" target="_blank">Compare</a>
                              </td>
                            @endif
                        @endguest
                    </tr>
                @empty
                    <x-data-tables.no-results colspan="8"/>
                @endforelse
            </x-data-tables.body>
        </x-data-tables.table>
      </x-core.cards.card>
    </div>
</div>
