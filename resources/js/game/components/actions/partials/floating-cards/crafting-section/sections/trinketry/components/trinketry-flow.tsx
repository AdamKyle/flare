import React, { ReactNode } from 'react';

import TrinketCostSummary from './trinket-cost-summary';
import TrinketSelection from './trinket-selection';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useTrinketryFlow } from '../hooks/use-trinketry-flow';
import { useOpenItemDetails } from '../../../../../../../chat-section/hooks/use-open-item-details';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const TrinketryFlow = (): ReactNode => {
  const {
    characterId,
    data,
    loading,
    error,
    mutationError,
    status,
    selectedItem,
    crafting,
    canCraft,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    itemsApi,
    resultPreview,
    selectItem,
    craftItem,
  } = useTrinketryFlow();

  const { openServerMessageItem } = useOpenItemDetails();

  const handleViewResultItem = (): void => {
    if (!resultPreview || resultPreview.inventory_slot_id === null) {
      return;
    }

    openServerMessageItem(characterId, resultPreview.inventory_slot_id);
  };

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Trinketry"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderError = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Trinketry.'}
    </Alert>
  );

  const renderAlerts = (): ReactNode => {
    if (!error && !mutationError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
    );
  };

  const renderItemSelection = (): ReactNode => (
    <TrinketSelection
      items={itemsApi.items}
      selectedItemId={selectedItem?.id ?? null}
      loading={itemsApi.loading}
      isLoadingMore={itemsApi.isLoadingMore}
      canLoadMore={itemsApi.canLoadMore}
      searchText={itemsApi.searchText}
      onSearch={itemsApi.setSearchText}
      onEndReached={itemsApi.onEndReached}
      onSelect={selectItem}
    />
  );

  const renderPreview = (): ReactNode => {
    if (resultPreview) {
      return (
        <CraftingActionPreview title="Item preview" status="success">
          <p
            role="status"
            aria-live="polite"
            className="text-sm text-emerald-700 dark:text-emerald-400"
          >
            {status ?? 'The Trinket was crafted.'}
          </p>
          <CraftingItemPreview
            item={resultPreview}
            on_name_click={
              resultPreview.inventory_slot_id !== null
                ? handleViewResultItem
                : undefined
            }
          />
        </CraftingActionPreview>
      );
    }

    if (!selectedItem) {
      return null;
    }

    return (
      <CraftingActionPreview
        title="Item preview"
        description="The selected Trinket is the item that will be attempted."
      >
        <CraftingItemPreview item={selectedItem.preview} />
        <TrinketCostSummary item={selectedItem} />
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <CraftingProgressActionButton
      idle_label="Craft Trinket"
      submitting_label="Crafting…"
      timeout_label="Craft another Trinket"
      submitting={crafting}
      is_timeout_active={isTimeoutActive}
      progress={progress}
      formatted_remaining={formattedRemaining}
      disabled={!canCraft || isCraftingDisabled}
      on_click={() => void craftItem()}
    />
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderError();
  }

  return (
    <CraftingActionLayout
      title="Trinketry"
      status={renderAlerts()}
      progress={
        <div className="space-y-2">
          <CraftingSkillXpProgress xp={data.skill_xp} />
          <CraftingInventoryProgress inventory_count={data.inventory_count} />
        </div>
      }
      form={renderItemSelection()}
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/trinketry"
      help_label="Trinketry help"
    />
  );
};

export default TrinketryFlow;
