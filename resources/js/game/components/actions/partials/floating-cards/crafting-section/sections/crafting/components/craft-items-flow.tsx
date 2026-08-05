import React, { ReactNode } from 'react';

import CraftActionPanel from './craft-action-panel';
import CraftProgressSummary from './craft-progress-summary';
import CraftResultAlert from './craft-result-alert';
import CraftTargetOptions from './craft-target-options';
import CraftTypeFilters from './craft-type-filters';
import CraftableItemPicker from './craftable-item-picker';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSectionScreenProps from '../../../types/crafting-section-screen-props';
import { useCraftItemsFlow } from '../hooks/use-craft-items-flow';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CraftItemsFlow = ({
  setActiveCraftingType,
}: CraftingSectionScreenProps): ReactNode => {
  const { filters, picker, targets, result, progress, action, navigation } =
    useCraftItemsFlow({ setActiveCraftingType });

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
      />
    );
  };

  const renderResult = (): ReactNode => {
    if (!picker.canShowItems) {
      return null;
    }

    return (
      <CraftResultAlert
        characterId={result.characterId}
        isCrafting={result.isCrafting}
        error={result.error}
        successMessage={result.successMessage}
        craftedInventorySlotId={result.craftedInventorySlotId}
        resultPreview={result.resultPreview}
      />
    );
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
        variant={ButtonVariant.SUCCESS}
        additional_css="w-full"
      />
    );
  };

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/crafting"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Crafting help (opens in a new tab)
    </a>
  );

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Craft an Item
        </h2>
      }
      form={renderForm()}
      progress={
        picker.canShowItems ? (
          <CraftProgressSummary craftingData={progress.displayedCraftingData} />
        ) : undefined
      }
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={renderHelpLink()}
    />
  );
};

export default CraftItemsFlow;
