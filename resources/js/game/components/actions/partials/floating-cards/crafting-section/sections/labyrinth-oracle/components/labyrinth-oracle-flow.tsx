import React, { ReactNode } from 'react';

import LabyrinthOracleCostSummary from './labyrinth-oracle-cost-summary';
import TransferItemSelection from './transfer-item-selection';
import CraftingActionButton from '../../../shared/components/crafting-action-button';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import CraftingActionPreview from '../../../shared/components/crafting-action-preview';
import CraftingItemPreview from '../../../shared/components/crafting-item-preview';
import LabyrinthOracleApiResponseDefinition from '../api/definitions/labyrinth-oracle-api-response-definition';
import { useLabyrinthOracleFlow } from '../hooks/use-labyrinth-oracle-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const LabyrinthOracleFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    status,
    sourceItem,
    destinationItem,
    hasEnoughItemsToTransfer,
    submitting,
    canSubmit,
    sourceResultPreview,
    destinationResultPreview,
    itemsApi,
    sourceId,
    destinationId,
    selectSource,
    selectDestination,
    submitTransfer,
  } = useLabyrinthOracleFlow();

  const renderLoadingState = (): ReactNode => (
    <IndeterminateProgressBar
      label="Loading Labyrinth Oracle items"
      variant={ProgressBarVariant.PRIMARY}
    />
  );

  const renderError = (): ReactNode => (
    <Alert variant={AlertVariant.DANGER}>
      {error ?? 'Unable to load the Labyrinth Oracle.'}
    </Alert>
  );

  const renderEmptyState = (): ReactNode => (
    <Alert variant={AlertVariant.INFO}>
      You need at least two eligible items in your inventory to transfer
      attributes between them.
    </Alert>
  );

  const renderStatus = (): ReactNode => {
    if (!error && !mutationError) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.DANGER}>{error ?? mutationError}</Alert>
    );
  };

  const renderItemSelection = (): ReactNode => (
    <TransferItemSelection
      items={itemsApi.items}
      sourceId={sourceId}
      destinationId={destinationId}
      loading={itemsApi.loading}
      isLoadingMore={itemsApi.isLoadingMore}
      canLoadMore={itemsApi.canLoadMore}
      searchText={itemsApi.searchText}
      onSearch={itemsApi.setSearchText}
      onEndReached={itemsApi.onEndReached}
      onSource={selectSource}
      onDestination={selectDestination}
    />
  );

  const renderCostSummary = (
    labyrinthOracleData: LabyrinthOracleApiResponseDefinition
  ): ReactNode => (
    <LabyrinthOracleCostSummary costs={labyrinthOracleData.costs} />
  );

  const renderResultPreview = (): ReactNode => {
    if (!sourceResultPreview && !destinationResultPreview) {
      return null;
    }

    return (
      <CraftingActionPreview title="Transfer preview" status="success">
        <p
          role="status"
          aria-live="polite"
          className="text-sm text-emerald-700 dark:text-emerald-400"
        >
          {status}
        </p>
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          {sourceResultPreview && (
            <div>
              <p className="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
                Source result
              </p>
              <CraftingItemPreview item={sourceResultPreview} />
            </div>
          )}
          {destinationResultPreview && (
            <div>
              <p className="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
                Destination result
              </p>
              <CraftingItemPreview item={destinationResultPreview} />
            </div>
          )}
        </div>
      </CraftingActionPreview>
    );
  };

  const renderPreview = (): ReactNode => {
    const resultPreview = renderResultPreview();

    if (resultPreview) {
      return resultPreview;
    }

    if (!sourceItem || !destinationItem || !data) {
      return null;
    }

    const hasDestinationGems = destinationItem.preview.socket_count > 0;

    return (
      <CraftingActionPreview
        title="Transfer preview"
        description="Enchantments, Holy Oils, and Gems move from the source to the destination."
      >
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
          <div>
            <p className="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
              Source
            </p>
            <CraftingItemPreview item={sourceItem.preview} />
          </div>
          <div>
            <p className="mb-1 text-xs font-semibold text-gray-600 dark:text-gray-400">
              Destination
            </p>
            <CraftingItemPreview item={destinationItem.preview} />
          </div>
        </div>
        <ul className="list-inside list-disc text-sm text-gray-700 dark:text-gray-300">
          {sourceItem.preview.item_prefix && (
            <li>Prefix moving: {sourceItem.preview.item_prefix.name}</li>
          )}
          {sourceItem.preview.item_suffix && (
            <li>Suffix moving: {sourceItem.preview.item_suffix.name}</li>
          )}
          {sourceItem.preview.holy_stacks_applied > 0 && (
            <li>
              Holy Stacks moving: {sourceItem.preview.holy_stacks_applied}
            </li>
          )}
          {sourceItem.preview.socket_count > 0 && (
            <li>Sockets/Gems moving: {sourceItem.preview.socket_count}</li>
          )}
          {hasDestinationGems && (
            <li>
              The destination&apos;s existing Gems will return to your Gem Bag
              when capacity allows.
            </li>
          )}
        </ul>
        {renderCostSummary(data)}
      </CraftingActionPreview>
    );
  };

  const renderAction = (): ReactNode => (
    <CraftingActionButton
      label={submitting ? 'Transferring…' : 'Transfer Attributes'}
      on_click={() => void submitTransfer()}
      disabled={!canSubmit}
    />
  );

  if (loading) {
    return renderLoadingState();
  }

  if (!data) {
    return renderError();
  }

  if (!hasEnoughItemsToTransfer) {
    return renderEmptyState();
  }

  return (
    <CraftingActionLayout
      title="Labyrinth Oracle"
      status={renderStatus()}
      form={renderItemSelection()}
      preview={renderPreview()}
      action={renderAction()}
      help_href="/information/labyrinth-oracle"
      help_label="Labyrinth Oracle help"
    />
  );
};

export default LabyrinthOracleFlow;
