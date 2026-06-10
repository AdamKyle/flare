import React, { ReactNode, useState } from 'react';

import CraftItemList from './craft-item-list';
import CraftingInventoryProgress from './crafting-inventory-progress';
import CraftingSkillXpProgress from './crafting-skill-xp-progress';
import { useInfiniteScroll } from '../../../../../character-sheet/partials/character-inventory/hooks/use-infinite-scroll';
import CraftableItemDefinition from '../api/definitions/craftable-item-definition';
import { useCraftItemApi } from '../api/hooks/use-craft-item-api';
import { useCraftableItemsApi } from '../api/hooks/use-craftable-items-api';
import { CraftingTypes } from '../enums/crafting-types';
import { useCraftingTimeout } from '../hooks/use-crafting-timeout';
import BaseSectionProps from '../screens/types/base-section-props';
import { armourTypeOptions, craftTypeOptions } from '../utils/crafting-options';

import { useGameData } from 'game-data/hooks/use-game-data';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import ProgressButton from 'ui/buttons/button-progress';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import Dropdown from 'ui/drop-down/drop-down';
import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import Input from 'ui/input/input';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const progressFillClass = (progress: number): string => {
  if (progress > 66) {
    return 'bg-emerald-700';
  }

  if (progress > 33) {
    return 'bg-emerald-500';
  }

  return 'bg-emerald-300 dark:bg-emerald-400';
};

const CraftItemsFlow = ({
  setActiveCraftingType,
}: BaseSectionProps): ReactNode => {
  const { gameData } = useGameData();

  const [selectedType, setSelectedType] = useState<string | null>(null);
  const [armourType, setArmourType] = useState<string | null>(null);
  const [selectedItem, setSelectedItem] =
    useState<CraftableItemDefinition | null>(null);
  const [searchInput, setSearchInput] = useState('');
  const [craftForNpc, setCraftForNpc] = useState(false);
  const [craftForEvent, setCraftForEvent] = useState(false);

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
  } = useCraftableItemsApi({
    characterId: gameData?.character?.id ?? 0,
    selectedType,
    armourType,
  });

  const {
    isCrafting,
    error,
    successMessage,
    craftingResponse,
    craftItem,
    clearMessages,
  } = useCraftItemApi({
    characterId: gameData?.character?.id ?? 0,
    selectedItem,
  });

  const { handleScroll } = useInfiniteScroll({ on_end_reached: onEndReached });

  const displayedCraftingData = craftingResponse ?? craftingData;

  const handleTypeChange = (item: DropdownItem) => {
    setSelectedType(String(item.value));
    setArmourType(null);
    setSelectedItem(null);
    setSearchInput('');
    setSearchText('');
    setCraftForNpc(false);
    setCraftForEvent(false);
    clearMessages();
  };

  const handleArmourTypeChange = (item: DropdownItem) => {
    setArmourType(String(item.value));
    setSelectedItem(null);
    clearMessages();
  };

  const handleSearch = (value: string) => {
    setSearchInput(value);
    setSearchText(value.trim());
    setSelectedItem(null);
  };

  const handleChangeType = () => {
    setSelectedType(null);
    setArmourType(null);
    setSelectedItem(null);
    setSearchInput('');
    setSearchText('');
    setCraftForNpc(false);
    setCraftForEvent(false);
    clearMessages();
  };

  const handleSelectItem = (item: CraftableItemDefinition) => {
    setSelectedItem(item);
    setCraftForNpc(false);
    setCraftForEvent(false);
    clearMessages();
  };

  const handleCraftForNpcChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    setCraftForNpc(event.target.checked);
  };

  const handleCraftForEventChange = (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
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

  const handleCraftItemsScroll = (event: React.UIEvent<HTMLDivElement>) => {
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

  const renderArmourTypeFieldset = () => {
    if (selectedType !== 'armour') {
      return null;
    }

    return (
      <fieldset>
        <legend className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
          Armour Type
        </legend>
        <Dropdown
          items={armourTypeOptions}
          on_select={handleArmourTypeChange}
          selection_placeholder="Select an armour type"
          pre_selected_item={selectedArmourTypeOption}
          force_clear={armourType === null}
          all_click_outside
          use_portal
        />
      </fieldset>
    );
  };

  const renderCraftItems = () => {
    if (loading) {
      return (
        <IndeterminateProgressBar
          label="Loading items..."
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    return (
      <CraftItemList
        items={items}
        selectedItem={selectedItem}
        loadingMore={isLoadingMore}
        handle_scroll={handleCraftItemsScroll}
        onSelect={handleSelectItem}
      />
    );
  };

  const renderCraftForNpcCheckbox = () => {
    if (!canCraftForNpc) {
      return null;
    }

    return (
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={craftForNpc}
          onChange={handleCraftForNpcChange}
        />
        Craft for the faction loyalty NPC
      </label>
    );
  };

  const renderCraftForEventCheckbox = () => {
    if (!canCraftForEvent) {
      return null;
    }

    return (
      <label className="flex items-center gap-2 text-sm">
        <input
          type="checkbox"
          checked={craftForEvent}
          onChange={handleCraftForEventChange}
        />
        Craft for the global event
      </label>
    );
  };

  const renderMessageArea = () => {
    if (isCrafting) {
      return (
        <IndeterminateProgressBar
          label="Crafting..."
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    if (!error && !successMessage) {
      return null;
    }

    return (
      <div>
        {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}
        {successMessage && (
          <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>
        )}
      </div>
    );
  };

  const renderTimeoutMessage = () => {
    if (!isTimeoutActive) {
      return null;
    }

    return (
      <p
        className="text-mango-tango-700 dark:text-mango-tango-300 mt-2 text-sm"
        role="status"
        aria-live="polite"
      >
        You can craft again in {formattedRemaining}.
      </p>
    );
  };

  const renderInventoryFullMessage = () => {
    if (!inventoryIsFull) {
      return null;
    }

    return (
      <p className="mt-2 text-sm text-rose-600 dark:text-rose-400">
        Your inventory is full.
      </p>
    );
  };

  const renderXpProgress = () => {
    if (!displayedCraftingData) {
      return null;
    }

    return <CraftingSkillXpProgress xp={displayedCraftingData.xp} />;
  };

  const renderInventoryProgress = () => {
    if (!displayedCraftingData) {
      return null;
    }

    return (
      <CraftingInventoryProgress
        inventory_count={displayedCraftingData.inventory_count}
      />
    );
  };

  const renderSelectedItem = () => {
    if (!selectedItem) {
      return null;
    }

    return (
      <div className="rounded-md border border-gray-400 p-3 dark:border-gray-600">
        <p className="font-semibold">{selectedItem.name}</p>
        <p className="text-sm">
          Cost: {formatNumberWithCommas(selectedItem.cost)} gold
        </p>
        <ProgressButton
          label={isCrafting ? 'Crafting...' : 'Craft Item'}
          on_click={handleCraft}
          variant={ButtonVariant.SUCCESS}
          progress={isCrafting ? 100 : progress}
          disabled={isCrafting || isCraftingDisabled || inventoryIsFull}
          additional_css="mt-3 w-full"
          progress_fill_class={progressFillClass(isCrafting ? 100 : progress)}
        />
        {renderTimeoutMessage()}
        {renderInventoryFullMessage()}
      </div>
    );
  };

  const renderCraftingStep = () => {
    if (!canShowItems) {
      return null;
    }

    return (
      <>
        <fieldset>
          <p className="prose dark:prose-invert my-4">
            You can search for items you want to craft or scroll the list below
            and select an item to craft. Once you select the item the craft item
            button will appear.
          </p>
          <Input
            value={searchInput}
            on_change={handleSearch}
            place_holder="Search craftable items"
            clearable
          />
        </fieldset>
        {renderCraftItems()}
        {renderCraftForNpcCheckbox()}
        {renderCraftForEventCheckbox()}
        {renderMessageArea()}
        <div className="my-2 mb-4">
          <div className="my-2">{renderXpProgress()}</div>
          <div>{renderInventoryProgress()}</div>
        </div>
        {renderSelectedItem()}
      </>
    );
  };

  return (
    <div className="space-y-4">
      <fieldset>
        <legend className="mb-1 text-sm font-medium text-gray-700 dark:text-gray-300">
          Craft Type
        </legend>
        <Dropdown
          items={craftTypeOptions}
          on_select={handleTypeChange}
          selection_placeholder="Select a craft type"
          pre_selected_item={selectedTypeOption}
          force_clear={selectedType === null}
          all_click_outside
          use_portal
        />
      </fieldset>

      {renderArmourTypeFieldset()}

      {renderCraftingStep()}

      <div className="grid grid-cols-2 gap-3">
        <Button
          label="Change Type"
          on_click={handleChangeType}
          variant={ButtonVariant.PRIMARY}
          disabled={selectedType === null}
        />
        <Button
          label="Close"
          on_click={handleClose}
          variant={ButtonVariant.DANGER}
        />
      </div>
    </div>
  );
};

export default CraftItemsFlow;
