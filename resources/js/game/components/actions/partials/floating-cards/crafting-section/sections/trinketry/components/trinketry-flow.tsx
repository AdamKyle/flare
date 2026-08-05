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

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const TrinketryFlow = (): ReactNode => {
  const {
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

  const renderResult = (): ReactNode => {
    if (!status) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.SUCCESS}>
        <span>{status}</span>
        {resultPreview && (
          <div className="mt-2">
            <CraftingItemPreview item={resultPreview} />
          </div>
        )}
      </Alert>
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
      variant={ButtonVariant.PRIMARY}
      additional_css="w-full sm:w-auto"
    />
  );

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/trinketry"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Trinketry help (opens in a new tab)
    </a>
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderError();
  }

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Trinketry
        </h2>
      }
      status={renderAlerts()}
      progress={
        <div className="space-y-2">
          <CraftingSkillXpProgress xp={data.skill_xp} />
          <CraftingInventoryProgress inventory_count={data.inventory_count} />
        </div>
      }
      form={renderItemSelection()}
      preview={renderPreview()}
      result={renderResult()}
      action={renderAction()}
      help_link={renderHelpLink()}
    />
  );
};

export default TrinketryFlow;
