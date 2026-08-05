import type { ChangeEvent, UIEvent } from 'react';
import { useState } from 'react';

import UseCraftItemsFlowDefinition from './definitions/use-craft-items-flow-definition';
import UseCraftItemsFlowParams from './definitions/use-craft-items-flow-params';
import { useInfiniteScroll } from '../../../../../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import { CraftingTypes } from '../../../enums/crafting-types';
import { useCraftingTimeout } from '../../../shared/hooks/use-crafting-timeout';
import CraftableItemDefinition from '../api/definitions/craftable-item-definition';
import { useCraftItemApi } from '../api/hooks/use-craft-item-api';
import { useCraftableItemsApi } from '../api/hooks/use-craftable-items-api';
import { armourTypeOptions, craftTypeOptions } from '../utils/crafting-options';

import { useGameData } from 'game-data/hooks/use-game-data';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const useCraftItemsFlow = ({
  setActiveCraftingType,
}: UseCraftItemsFlowParams): UseCraftItemsFlowDefinition => {
  const { gameData } = useGameData();

  const [selectedType, setSelectedType] = useState<string | null>(null);
  const [armourType, setArmourType] = useState<string | null>(null);
  const [selectedItemId, setSelectedItemId] = useState<number | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [craftForNpc, setCraftForNpc] = useState(false);
  const [craftForEvent, setCraftForEvent] = useState(false);

  const characterId = gameData?.character?.id ?? 0;

  const { isTimeoutActive, isCraftingDisabled, progress, formattedRemaining } =
    useCraftingTimeout(gameData?.character);

  const {
    items,
    craftingData,
    loading,
    isLoadingMore,
    canLoadMore,
    onEndReached,
    setSearchText,
  } = useCraftableItemsApi({ characterId, selectedType, armourType });

  const selectedItem = items.find((item) => item.id === selectedItemId) ?? null;

  const {
    isCrafting,
    error,
    successMessage,
    craftingResponse,
    craftedInventorySlotId,
    resultPreview,
    craftItem,
    clearMessages,
  } = useCraftItemApi({ characterId, selectedItem });

  const { handleScroll } = useInfiniteScroll({ on_end_reached: onEndReached });

  const displayedCraftingData = craftingResponse ?? craftingData;

  const handleTypeChange = (item: DropdownItem) => {
    setSelectedType(String(item.value));
    setArmourType(null);
    setSelectedItemId(null);
    setSearchInput('');
    setSearchText('');
    setCraftForNpc(false);
    setCraftForEvent(false);
    clearMessages();
  };

  const handleArmourTypeChange = (item: DropdownItem) => {
    setArmourType(String(item.value));
    setSelectedItemId(null);
    clearMessages();
  };

  const handleSearch = (value: string) => {
    setSearchInput(value);
    setSearchText(value.trim());
    setSelectedItemId(null);
  };

  const handleChangeType = () => {
    setSelectedType(null);
    setArmourType(null);
    setSelectedItemId(null);
    setSearchInput('');
    setSearchText('');
    setCraftForNpc(false);
    setCraftForEvent(false);
    clearMessages();
  };

  const handleSelectItem = (item: CraftableItemDefinition) => {
    setSelectedItemId(item.id);
    setCraftForNpc(false);
    setCraftForEvent(false);
    clearMessages();
  };

  const handleCraftForNpcChange = (event: ChangeEvent<HTMLInputElement>) => {
    setCraftForNpc(event.target.checked);
  };

  const handleCraftForEventChange = (event: ChangeEvent<HTMLInputElement>) => {
    setCraftForEvent(event.target.checked);
  };

  const handleClose = () => {
    setActiveCraftingType(CraftingTypes.HOME);
  };

  const handleCraft = () => {
    if (!selectedItem || isCraftingDisabled || isCrafting) {
      return;
    }

    craftItem(craftForNpc, craftForEvent);
  };

  const handleCraftItemsScroll = (event: UIEvent<HTMLDivElement>) => {
    if (loading || isLoadingMore || !canLoadMore) {
      return;
    }

    handleScroll(event);
  };

  const canShowItems =
    selectedType !== null && (selectedType !== 'armour' || armourType !== null);

  const inventoryIsFull = Boolean(
    displayedCraftingData &&
    displayedCraftingData.inventory_count.current_count >=
      displayedCraftingData.inventory_count.max_inventory
  );

  const selectedTypeOption = craftTypeOptions.find(
    (option) => option.value === selectedType
  );

  const selectedArmourTypeOption = armourTypeOptions.find(
    (option) => option.value === armourType
  );

  const canCraftForNpc = Boolean(
    selectedItem &&
    displayedCraftingData?.show_craft_for_npc &&
    gameData?.character?.current_fame_tasks?.some(
      (task) => task.item_id === selectedItem.id
    )
  );

  const canCraftForEvent = Boolean(
    selectedItem && displayedCraftingData?.show_craft_for_event
  );

  return {
    filters: {
      selectedType,
      armourType,
      selectedTypeOption,
      selectedArmourTypeOption,
      handleTypeChange,
      handleArmourTypeChange,
      handleChangeType,
    },
    picker: {
      items,
      selectedItem,
      searchInput,
      loading,
      isLoadingMore,
      canShowItems,
      handleSearch,
      handleSelectItem,
      handleCraftItemsScroll,
    },
    targets: {
      craftForNpc,
      craftForEvent,
      canCraftForNpc,
      canCraftForEvent,
      handleCraftForNpcChange,
      handleCraftForEventChange,
    },
    result: {
      characterId,
      error,
      successMessage,
      craftedInventorySlotId,
      resultPreview,
      isCrafting,
    },
    progress: {
      displayedCraftingData,
      isTimeoutActive,
      formattedRemaining,
      progress,
    },
    action: {
      isCraftingDisabled,
      inventoryIsFull,
      handleCraft,
    },
    navigation: {
      handleClose,
    },
  };
};
