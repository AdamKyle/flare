import React, { ReactNode } from 'react';

import AlchemyCostSummary from './alchemy-cost-summary';
import AlchemyItemSelection from './alchemy-item-selection';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingInventoryProgress from '../../../shared/components/crafting-inventory-progress';
import CraftingProgressActionButton from '../../../shared/components/crafting-progress-action-button';
import CraftingResultNameButton from '../../../shared/components/crafting-result-name-button';
import CraftingSkillXpProgress from '../../../shared/components/crafting-skill-xp-progress';
import { useAlchemyFlow } from '../hooks/use-alchemy-flow';
import { planeTextItemColors } from '../../../../../../../character-sheet/partials/character-inventory/styles/backpack-item-styles';
import { useOpenCharacterUsableInventory } from '../../../../../../../character-sheet/partials/character-inventory/hooks/use-open-character-usable-inventory';
import UsableItemEffects from '../../../../../../../../reusable-components/usable-item/usable-item-effects';

import { formatNumberWithCommas } from 'game-utils/format-number';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const AlchemyFlow = (): ReactNode => {
  const {
    characterId,
    data,
    loading,
    error,
    mutationError,
    selectedItem,
    transmuting,
    canTransmute,
    isTimeoutActive,
    isCraftingDisabled,
    progress,
    formattedRemaining,
    itemsApi,
    alchemyResult,
    selectItem,
    transmuteItem,
  } = useAlchemyFlow();

  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id: characterId,
  });

  const handleViewResultItem = (): void => {
    if (!alchemyResult) {
      return;
    }

    openUsableInventory(alchemyResult.item_preview);
  };

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Alchemy"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderError = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load Alchemy.'}
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
    <AlchemyItemSelection
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

  const renderResultMessage = (): ReactNode => {
    if (!alchemyResult) {
      return null;
    }

    return (
      <p
        role="status"
        aria-live="polite"
        className="text-sm text-emerald-700 dark:text-emerald-400"
      >
        {'You created '}
        <strong>{formatNumberWithCommas(alchemyResult.amount_created)}</strong>
        {' x '}
        {alchemyResult.name}
        {'. You now own '}
        {formatNumberWithCommas(alchemyResult.current_amount)}
        {'.'}
      </p>
    );
  };

  const renderPendingPreviewDetails = (): ReactNode => (
    <>
      <p className="font-semibold text-gray-900 dark:text-gray-100">
        {selectedItem?.name}
      </p>
      <p className="text-xs text-gray-500 dark:text-gray-400">
        Type: {selectedItem?.type} &bull; Quantity Attempted: 1
      </p>
      {selectedItem && <AlchemyCostSummary item={selectedItem} />}
    </>
  );

  const renderSuccessPreviewDetails = (): ReactNode => {
    if (!alchemyResult) {
      return null;
    }

    return (
      <>
        <CraftingResultNameButton
          name={alchemyResult.item_preview.name}
          class_name={planeTextItemColors(alchemyResult.item_preview)}
          on_click={handleViewResultItem}
        />
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          {alchemyResult.item_preview.description}
        </p>
        <p className="text-xs text-gray-500 dark:text-gray-400">
          Type: {alchemyResult.item_preview.type}
        </p>
        <UsableItemEffects item={alchemyResult.item_preview} />
      </>
    );
  };

  const renderPreview = (): ReactNode => {
    if (!selectedItem) {
      return null;
    }

    const isSuccess = alchemyResult !== null;

    return (
      <CraftingActionPreview
        title="Item preview"
        description={
          isSuccess ? undefined : 'You will attempt to create one of this item.'
        }
        status={isSuccess ? 'success' : 'default'}
      >
        {renderResultMessage()}
        {isSuccess ? renderSuccessPreviewDetails() : renderPendingPreviewDetails()}
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <CraftingProgressActionButton
      idle_label="Transmute"
      submitting_label="Transmuting…"
      timeout_label="Transmute again"
      submitting={transmuting}
      is_timeout_active={isTimeoutActive}
      progress={progress}
      formatted_remaining={formattedRemaining}
      disabled={!canTransmute || isCraftingDisabled}
      on_click={() => void transmuteItem()}
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
      title="Alchemy"
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
      help_href="/information/alchemy"
      help_label="Alchemy help"
    />
  );
};

export default AlchemyFlow;
