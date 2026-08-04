import React, { ReactNode } from 'react';

import CraftActionPanel from './craft-action-panel';
import CraftProgressSummary from './craft-progress-summary';
import CraftResultAlert from './craft-result-alert';
import CraftTargetOptions from './craft-target-options';
import CraftTypeFilters from './craft-type-filters';
import CraftableItemPicker from './craftable-item-picker';
import CraftingSectionScreenProps from '../../../types/crafting-section-screen-props';
import { useCraftItemsFlow } from '../hooks/use-craft-items-flow';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftItemsFlow = ({
  setActiveCraftingType,
}: CraftingSectionScreenProps): ReactNode => {
  const { filters, picker, targets, result, progress, action, navigation } =
    useCraftItemsFlow({ setActiveCraftingType });

  const renderCraftingStep = () => {
    if (!picker.canShowItems) {
      return null;
    }

    return (
      <>
        <CraftableItemPicker
          searchInput={picker.searchInput}
          items={picker.items}
          selectedItem={picker.selectedItem}
          loading={picker.loading}
          loadingMore={picker.isLoadingMore}
          onSearch={picker.handleSearch}
          onScroll={picker.handleCraftItemsScroll}
          onSelect={picker.handleSelectItem}
        />
        <CraftTargetOptions
          canCraftForNpc={targets.canCraftForNpc}
          canCraftForEvent={targets.canCraftForEvent}
          craftForNpc={targets.craftForNpc}
          craftForEvent={targets.craftForEvent}
          onCraftForNpcChange={targets.handleCraftForNpcChange}
          onCraftForEventChange={targets.handleCraftForEventChange}
        />
        <CraftResultAlert
          characterId={result.characterId}
          isCrafting={result.isCrafting}
          error={result.error}
          successMessage={result.successMessage}
          craftedInventorySlotId={result.craftedInventorySlotId}
          craftedItemDetails={result.craftedItemDetails}
        />
        <CraftProgressSummary craftingData={progress.displayedCraftingData} />
        <CraftActionPanel
          selectedItem={picker.selectedItem}
          isCrafting={result.isCrafting}
          isCraftingDisabled={action.isCraftingDisabled}
          inventoryIsFull={action.inventoryIsFull}
          isTimeoutActive={progress.isTimeoutActive}
          formattedRemaining={progress.formattedRemaining}
          progress={progress.progress}
          onCraft={action.handleCraft}
        />
      </>
    );
  };

  return (
    <div className="space-y-4">
      <CraftTypeFilters
        selectedType={filters.selectedType}
        armourType={filters.armourType}
        selectedTypeOption={filters.selectedTypeOption}
        selectedArmourTypeOption={filters.selectedArmourTypeOption}
        onTypeChange={filters.handleTypeChange}
        onArmourTypeChange={filters.handleArmourTypeChange}
      />

      {renderCraftingStep()}

      <div className="grid grid-cols-2 gap-3">
        <Button
          label="Change Type"
          on_click={filters.handleChangeType}
          variant={ButtonVariant.PRIMARY}
          disabled={filters.selectedType === null}
        />
        <Button
          label="Close"
          on_click={navigation.handleClose}
          variant={ButtonVariant.DANGER}
        />
      </div>
    </div>
  );
};

export default CraftItemsFlow;
