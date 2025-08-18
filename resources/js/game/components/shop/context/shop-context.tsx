import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import React, { createContext, useState, useEffect } from 'react';

import ShopContextDefinition from './definitions/shop-context-definition';
import ShopProviderProps from './types/shop-provider-props';
import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { ShopApiUrls } from '../api/enums/shop-api-urls';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const ShopContext = createContext<ShopContextDefinition | undefined>(undefined);

const ShopProvider = ({ characterId, children }: ShopProviderProps) => {
  const [searchText, setSearchText] = useState<string>('');
  const [selectedCost, setSelectedCost] = useState<DropdownItem | null>(null);
  const [selectedType, setSelectedType] = useState<DropdownItem | null>(null);

  const {
    data,
    loading,
    error,
    onEndReached,
    setSearchText: hookSetSearchText,
    setFilters: hookSetFilters,
  } = UsePaginatedApiHandler<EquippableItemWithBase>({
    url: ShopApiUrls.VISIT_SHOP,
    urlParams: { character: characterId },
  });

  const { handleScroll } = useInfiniteScroll({
    on_end_reached: onEndReached,
  });

  useEffect(() => {
    hookSetSearchText(searchText);
  }, [searchText, hookSetSearchText]);

  useEffect(() => {
    const filters: Record<string, string> = {};
    if (selectedCost !== null) {
      filters.sort_cost = selectedCost.value as string;
    }
    if (selectedType !== null) {
      filters.type = selectedType.value as string;
    }
    hookSetFilters(() => filters);
  }, [selectedCost, selectedType, hookSetFilters]);

  return (
    <ShopContext.Provider
      value={{
        data,
        loading,
        error,
        handleScroll,
        searchText,
        setSearchText,
        selectedCost,
        setSelectedCost,
        selectedType,
        setSelectedType,
      }}
    >
      {children}
    </ShopContext.Provider>
  );
};

export { ShopProvider, ShopContext };
