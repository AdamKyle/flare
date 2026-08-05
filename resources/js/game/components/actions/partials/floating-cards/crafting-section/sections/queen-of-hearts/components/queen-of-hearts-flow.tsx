import React, { ReactNode } from 'react';

import QueenActionSelection from './queen-action-selection';
import QueenMoveAffixesForm from './queen-move-affixes-form';
import QueenRerollForm from './queen-reroll-form';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import { QueenAction } from '../enums/queen-action';
import { useQueenOfHeartsFlow } from '../hooks/use-queen-of-hearts-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
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

  const renderStatus = (): ReactNode => {
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

  const renderSelectionForm = (): ReactNode => {
    if (hasData) {
      return <QueenActionSelection onSelect={selectAction} />;
    }

    if (error) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        Unable to load your inventory right now.
      </Alert>
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

  if (data && action === QueenAction.REROLL_ITEM) {
    return (
      <QueenRerollForm
        data={data}
        characterId={characterId}
        rootStatus={renderStatus()}
        helpLink={renderHelpLink()}
        onDataReplaced={replaceData}
        onSuccess={handleRerollSuccess}
        onChangeAction={resetAction}
      />
    );
  }

  if (data && action === QueenAction.MOVE_ENCHANTS) {
    return (
      <QueenMoveAffixesForm
        data={data}
        characterId={characterId}
        rootStatus={renderStatus()}
        helpLink={renderHelpLink()}
        onDataReplaced={replaceData}
        onSuccess={handleMoveSuccess}
        onChangeAction={resetAction}
      />
    );
  }

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Queen of Hearts
        </h2>
      }
      status={renderStatus()}
      form={renderSelectionForm()}
      help_link={renderHelpLink()}
    />
  );
};

export default QueenOfHeartsFlow;
