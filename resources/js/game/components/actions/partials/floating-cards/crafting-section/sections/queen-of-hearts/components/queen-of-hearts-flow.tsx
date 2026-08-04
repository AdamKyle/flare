import React, { ReactNode } from 'react';

import QueenActionSelection from './queen-action-selection';
import QueenMoveAffixesForm from './queen-move-affixes-form';
import QueenRerollForm from './queen-reroll-form';
import { QueenAction } from '../enums/queen-action';
import { useQueenOfHeartsFlow } from '../hooks/use-queen-of-hearts-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const QueenOfHeartsFlow = (): ReactNode => {
  const {
    characterId,
    data,
    loading,
    error,
    status,
    action,
    hasData,
    replaceData,
    selectAction,
    resetAction,
    handleRerollSuccess,
    handleMoveSuccess,
  } = useQueenOfHeartsFlow();

  const renderAlerts = (): ReactNode => {
    if (!error && !status) {
      return null;
    }

    return (
      <div className="space-y-2">
        {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}
        {status && <Alert variant={AlertVariant.SUCCESS}>{status}</Alert>}
      </div>
    );
  };

  const renderEmptyState = (): ReactNode => {
    if (hasData || error) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        Unable to load your inventory right now.
      </Alert>
    );
  };

  const renderActionSelection = (): ReactNode => {
    if (action || !hasData) {
      return null;
    }

    return <QueenActionSelection onSelect={selectAction} />;
  };

  const renderActiveActionForm = (): ReactNode => {
    if (!data || !action) {
      return null;
    }

    if (action === QueenAction.REROLL_ITEM) {
      return (
        <QueenRerollForm
          data={data}
          characterId={characterId}
          onDataReplaced={replaceData}
          onSuccess={handleRerollSuccess}
        />
      );
    }

    return (
      <QueenMoveAffixesForm
        data={data}
        characterId={characterId}
        onDataReplaced={replaceData}
        onSuccess={handleMoveSuccess}
      />
    );
  };

  const renderChangeActionButton = (): ReactNode => {
    if (!action) {
      return null;
    }

    return (
      <Button
        label="Change Action"
        on_click={resetAction}
        variant={ButtonVariant.PRIMARY}
      />
    );
  };

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/random-enchants"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Random enchant help (opens in a new tab)
    </a>
  );

  if (loading) {
    return (
      <IndeterminateProgressBar
        label="Loading Queen of Hearts"
        variant={ProgressBarVariant.PRIMARY}
      />
    );
  }

  return (
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Queen of Hearts</h2>

      {renderAlerts()}
      {renderEmptyState()}
      {renderActionSelection()}
      {renderActiveActionForm()}
      {renderChangeActionButton()}
      {renderHelpLink()}
    </div>
  );
};

export default QueenOfHeartsFlow;
