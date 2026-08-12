import React, { ReactNode } from 'react';

import EnchantingAffixSelection from './enchanting-affix-selection';
import EnchantingCostSummary from './enchanting-cost-summary';
import EnchantingItemSelection from './enchanting-item-selection';
import EnchantingSourceSelection from './enchanting-source-selection';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useOpenCraftedItem } from '../../../shared/hooks/use-open-crafted-item';
import { useEnchantingFlow } from '../hooks/use-enchanting-flow';
import { buildDecoratedItemName } from '../utils/build-decorated-item-name';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const ENCHANT_FAILED_MESSAGE =
  'The enchantment failed. Check Server Messages for the full outcome.';

const ALL_ITEMS_ENCHANTED_MESSAGE =
  'All items are currently enchanted, be careful child - all choices carry consequences.';

const EnchantingFlow = (): ReactNode => {
  const {
    characterId,
    data,
    loading,
    error,
    mutationError,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    hasEventChoice,
    effectiveSource,
    effectiveSlotId,
    selectedItemName,
    allItemsEnchanted,
    totalCost,
    submitting,
    canSubmit,
    lastEnchantSucceeded,
    resultPreview,
    itemsApi,
    prefixApi,
    suffixApi,
    selectSource,
    selectSlot,
    selectPrefix,
    selectSuffix,
    submitEnchant,
  } = useEnchantingFlow();

  const { openCraftedInventoryItem } = useOpenCraftedItem();

  const handleViewResultItem = (): void => {
    if (!resultPreview || resultPreview.inventory_slot_id === null) {
      return;
    }

    openCraftedInventoryItem(characterId, resultPreview.inventory_slot_id);
  };

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading enchanting options"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderEmptyState = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Enchanting.'}
    </Alert>
  );

  const renderError = (): ReactNode => {
    if (!error && !mutationError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
    );
  };

  const renderXpProgress = (): ReactNode => {
    if (!data) {
      return null;
    }

    return <CraftingSkillXpProgress xp={data.skill_xp} />;
  };

  const renderInventoryProgress = (): ReactNode => {
    if (!data || !data.inventory_count) {
      return null;
    }

    return <CraftingInventoryProgress inventory_count={data.inventory_count} />;
  };

  const renderSourceSelection = (): ReactNode => {
    if (!hasEventChoice || effectiveSource !== null) {
      return null;
    }

    return <EnchantingSourceSelection onSelect={selectSource} />;
  };

  const renderItemSelection = (): ReactNode => {
    if (!data || effectiveSource === null) {
      return null;
    }

    return (
      <EnchantingItemSelection
        items={itemsApi.items}
        loading={itemsApi.loading}
        isLoadingMore={itemsApi.isLoadingMore}
        canLoadMore={itemsApi.canLoadMore}
        searchText={itemsApi.searchText}
        onSearch={itemsApi.setSearchText}
        onEndReached={itemsApi.onEndReached}
        selectedSlotId={effectiveSlotId}
        selectedItemName={selectedItemName}
        onSelect={selectSlot}
      />
    );
  };

  const renderAllItemsEnchantedMessage = (): ReactNode => {
    if (!allItemsEnchanted) {
      return null;
    }

    return (
      <p
        role="status"
        aria-live="polite"
        className="text-sm text-gray-700 dark:text-gray-300"
      >
        {ALL_ITEMS_ENCHANTED_MESSAGE}
      </p>
    );
  };

  const renderAffixSelection = (): ReactNode => {
    if (!data || effectiveSource === null) {
      return null;
    }

    return (
      <EnchantingAffixSelection
        prefix={{
          items: prefixApi.affixes,
          loading: prefixApi.loading,
          isLoadingMore: prefixApi.isLoadingMore,
          canLoadMore: prefixApi.canLoadMore,
          searchText: prefixApi.searchText,
          onSearch: prefixApi.setSearchText,
          onEndReached: prefixApi.onEndReached,
        }}
        suffix={{
          items: suffixApi.affixes,
          loading: suffixApi.loading,
          isLoadingMore: suffixApi.isLoadingMore,
          canLoadMore: suffixApi.canLoadMore,
          searchText: suffixApi.searchText,
          onSearch: suffixApi.setSearchText,
          onEndReached: suffixApi.onEndReached,
        }}
        onPrefix={selectPrefix}
        onSuffix={selectSuffix}
      />
    );
  };

  const renderForm = (): ReactNode => (
    <div className="space-y-3">
      {renderSourceSelection()}
      {renderItemSelection()}
      {renderAllItemsEnchantedMessage()}
      {renderAffixSelection()}
    </div>
  );

  const renderPreviewContent = (): ReactNode => {
    if (lastEnchantSucceeded === true) {
      return (
        <>
          <p className="text-sm text-emerald-700 dark:text-emerald-400">
            Your enchantment was applied.
          </p>
          {resultPreview && (
            <CraftingItemPreview
              item={resultPreview}
              display_name={buildDecoratedItemName(resultPreview)}
              on_name_click={
                resultPreview.inventory_slot_id !== null
                  ? handleViewResultItem
                  : undefined
              }
            />
          )}
        </>
      );
    }

    if (lastEnchantSucceeded === false) {
      return (
        <p className="text-sm text-rose-600 dark:text-rose-400">
          {ENCHANT_FAILED_MESSAGE}
        </p>
      );
    }

    return <EnchantingCostSummary totalCost={totalCost} />;
  };

  const renderPreview = (): ReactNode => {
    if (effectiveSource === null) {
      return null;
    }

    const previewStatus =
      lastEnchantSucceeded === true
        ? 'success'
        : lastEnchantSucceeded === false
          ? 'danger'
          : 'default';

    return (
      <CraftingActionPreview
        title="Enchanting Preview"
        description={
          lastEnchantSucceeded === null
            ? 'The final result depends on the enchanting roll and is not shown until the attempt completes.'
            : undefined
        }
        status={previewStatus}
      >
        {renderPreviewContent()}
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => {
    if (effectiveSource === null) {
      return null;
    }

    return (
      <CraftingProgressActionButton
        idle_label="Enchant Item"
        submitting_label="Enchanting…"
        timeout_label="Enchant again"
        submitting={submitting}
        is_timeout_active={isTimeoutActive}
        progress={progress}
        formatted_remaining={formattedRemaining}
        disabled={!canSubmit || isCraftingDisabled}
        on_click={() => void submitEnchant()}
      />
    );
  };

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderEmptyState();
  }

  return (
    <CraftingActionLayout
      title="Enchanting"
      status={renderError()}
      progress={
        <div className="space-y-2">
          {renderXpProgress()}
          {renderInventoryProgress()}
        </div>
      }
      form={renderForm()}
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/enchanting"
      help_label="Enchanting information"
    />
  );
};

export default EnchantingFlow;
