import UsePaginatedApiHandler from 'api-handler/hooks/use-paginated-api-handler';
import React, { createContext, useState, useEffect, useMemo } from 'react';

import ShopContextDefinition from './definitions/shop-context-definition';
import ShopProviderProps from './types/shop-provider-props';
import { EquippableItemWithBase } from '../../../api-definitions/items/equippable-item-definitions/base-equippable-item-definition';
import { useInfiniteScroll } from '../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { ShopApiUrls } from '../api/enums/shop-api-urls';
import { usePurchaseItem } from '../api/hooks/use-purchase-item';

import CharacterSheetDefinition from 'game-data/api-data-definitions/character/character-sheet-definition';
import { useGameData } from 'game-data/hooks/use-game-data';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

const ShopContext = createContext<ShopContextDefinition | undefined>(undefined);

const ShopProvider = ({ children }: ShopProviderProps) => {
  const { gameData, updateCharacter } = useGameData();

  const [searchText, setSearchText] = useState<string>('');
  const [selectedCost, setSelectedCost] = useState<DropdownItem | null>(null);
  const [selectedType, setSelectedType] = useState<DropdownItem | null>(null);

  const characterId = gameData?.character?.id ?? 0;

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

  const handlePurchaseSuccess = (
    character: Partial<CharacterSheetDefinition>
  ) => {
    updateCharacter(character);
  };

  const {
    setRequestParams: setShopPurchaseRequestParams,
    error: purchaseError,
    successMessage: purchaseSuccessMessage,
    loading: purchaseLoading,
  } = usePurchaseItem({
    on_success: handlePurchaseSuccess,
    character_id: characterId,
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

  const inventoryIsFull = useMemo(() => {
    if (!gameData?.character) {
      return true;
    }

    return (
      gameData.character.inventory_count.data.inventory_count >=
      gameData.character.inventory_count.data.inventory_max
    );
  }, [gameData]);

  return (
    <ShopContext.Provider
      value={{
        data,
        loading,
        error,
        purchaseError,
        purchaseSuccessMessage,
        purchaseLoading,
        handleScroll,
        searchText,
        setSearchText,
        selectedCost,
        setSelectedCost,
        selectedType,
        setSelectedType,
        setShopPurchaseRequestParams,
        inventoryIsFull,
        character: gameData?.character,
        updateCharacter,
      }}
    >
      {children}
    </ShopContext.Provider>
  );
};

export { ShopProvider, ShopContext };
