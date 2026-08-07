import React, { ReactNode } from 'react';

import CraftActionPanel from './craft-action-panel';
import CraftProgressSummary from './craft-progress-summary';
import CraftTargetOptions from './craft-target-options';
import CraftTypeFilters from './craft-type-filters';
import CraftableItemPicker from './craftable-item-picker';
import CraftingActionButton from '../../../shared/components/crafting-action-button';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSectionScreenProps from '../../../types/crafting-section-screen-props';
import { useCraftItemsFlow } from '../hooks/use-craft-items-flow';
import { useOpenItemDetails } from '../../../../../../../chat-section/hooks/use-open-item-details';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftItemsFlow = ({
  setActiveCraftingType,
}: CraftingSectionScreenProps): ReactNode => {
  const { filters, picker, targets, result, progress, action, navigation } =
    useCraftItemsFlow({ setActiveCraftingType });
  const { openServerMessageItem } = useOpenItemDetails();

  const canViewCraftedItem =
    result.isCraftSuccessful && result.craftedInventorySlotId !== null;

  const handleViewCraftedItem = (): void => {
    if (!result.isCraftSuccessful || result.craftedInventorySlotId === null) {
      return;
    }

    openServerMessageItem(result.characterId, result.craftedInventorySlotId);
  };

  const renderForm = (): ReactNode => {
    return (
      <>
        <CraftTypeFilters
          selectedType={filters.selectedType}
          armourType={filters.armourType}
          selectedTypeOption={filters.selectedTypeOption}
          selectedArmourTypeOption={filters.selectedArmourTypeOption}
          onTypeChange={filters.handleTypeChange}
          onArmourTypeChange={filters.handleArmourTypeChange}
        />

        {picker.canShowItems && (
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
          </>
        )}

        <div className="grid grid-cols-2 gap-3">
          <CraftingActionButton
            label="Change Type"
            on_click={filters.handleChangeType}
            variant={ButtonVariant.PRIMARY}
            disabled={filters.selectedType === null}
          />
          <CraftingActionButton
            label="Close"
            on_click={navigation.handleClose}
            variant={ButtonVariant.DANGER}
          />
        </div>
      </>
    );
  };

  const renderPreview = (): ReactNode => {
    if (!picker.canShowItems || !picker.selectedItem) {
      return null;
    }

    return (
      <CraftActionPanel
        selectedItem={picker.selectedItem}
        inventoryIsFull={action.inventoryIsFull}
        isCraftSuccessful={result.isCraftSuccessful}
        onViewCraftedItem={canViewCraftedItem ? handleViewCraftedItem : undefined}
      />
    );
  };

  const renderStatus = (): ReactNode => {
    if (!result.error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{result.error}</Alert>;
  };

  const renderAction = (): ReactNode => {
    if (!picker.canShowItems || !picker.selectedItem) {
      return null;
    }

    return (
      <CraftingProgressActionButton
        idle_label="Craft Item"
        submitting_label="Crafting…"
        timeout_label="Craft again"
        submitting={result.isCrafting}
        is_timeout_active={progress.isTimeoutActive}
        progress={progress.progress}
        formatted_remaining={progress.formattedRemaining}
        disabled={action.isCraftingDisabled || action.inventoryIsFull}
        on_click={action.handleCraft}
      />
    );
  };

  return (
    <CraftingActionLayout
      title="Crafting"
      status={renderStatus()}
      form={renderForm()}
      progress={
        picker.canShowItems ? (
          <CraftProgressSummary craftingData={progress.displayedCraftingData} />
        ) : undefined
      }
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/crafting"
      help_label="Crafting help"
    />
  );
};

export default CraftItemsFlow;
