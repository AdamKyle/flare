import React, { ReactNode } from 'react';

import LabyrinthOracleCostSummary from './labyrinth-oracle-cost-summary';
import TransferItemSelection from './transfer-item-selection';
import LabyrinthOracleApiResponseDefinition from '../api/definitions/labyrinth-oracle-api-response-definition';
import { useLabyrinthOracleFlow } from '../hooks/use-labyrinth-oracle-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const LabyrinthOracleFlow = (): ReactNode => {
  const {
    data,
    loading,
    error,
    mutationError,
    status,
    sourceId,
    destinationId,
    sourceItem,
    destinationItem,
    hasEnoughItemsToTransfer,
    submitting,
    canSubmit,
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

  const renderErrorAlert = (): ReactNode => {
    const combinedError = error ?? mutationError;

    if (!combinedError) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{combinedError}</Alert>;
  };

  const renderStatusAlert = (): ReactNode => {
    if (!status) {
      return null;
    }

    return <Alert variant={AlertVariant.SUCCESS}>{status}</Alert>;
  };

  const renderAlerts = (): ReactNode => (
    <>
      {renderErrorAlert()}
      {renderStatusAlert()}
    </>
  );

  const renderItemSelection = (
    labyrinthOracleData: LabyrinthOracleApiResponseDefinition
  ): ReactNode => (
    <TransferItemSelection
      inventory={labyrinthOracleData.inventory}
      sourceId={sourceId}
      destinationId={destinationId}
      onSource={selectSource}
      onDestination={selectDestination}
    />
  );

  const renderCostSummary = (
    labyrinthOracleData: LabyrinthOracleApiResponseDefinition
  ): ReactNode => (
    <LabyrinthOracleCostSummary costs={labyrinthOracleData.costs} />
  );

  const renderSelectionSummary = (): ReactNode => {
    if (!sourceItem || !destinationItem) {
      return null;
    }

    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">
        Transfer attributes from{' '}
        <span className="font-semibold">{sourceItem.affix_name}</span> to{' '}
        <span className="font-semibold">{destinationItem.affix_name}</span>.
      </p>
    );
  };

  const renderAction = (): ReactNode => (
    <div className="space-y-2">
      {renderSelectionSummary()}

      <Button
        label={submitting ? 'Transferring…' : 'Transfer Attributes'}
        on_click={() => void submitTransfer()}
        variant={ButtonVariant.PRIMARY}
        disabled={!canSubmit}
      />
    </div>
  );

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/labyrinth-oracle"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Labyrinth Oracle help (opens in a new tab)
    </a>
  );

  const renderContent = (
    labyrinthOracleData: LabyrinthOracleApiResponseDefinition
  ): ReactNode => (
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Labyrinth Oracle</h2>

      {renderAlerts()}

      <p>
        Enchantments, Holy Oils, and Gems move from the source to the
        destination. Existing destination gems may be returned to your Gem Bag
        when capacity allows.
      </p>

      {renderItemSelection(labyrinthOracleData)}

      {renderCostSummary(labyrinthOracleData)}

      {renderAction()}

      {renderHelpLink()}
    </div>
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

  return renderContent(data);
};

export default LabyrinthOracleFlow;
