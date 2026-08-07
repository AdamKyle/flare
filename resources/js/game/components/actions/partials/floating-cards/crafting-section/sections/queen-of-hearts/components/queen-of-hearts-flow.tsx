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
    action,
    hasData,
    replaceData,
    selectAction,
    resetAction,
  } = useQueenOfHeartsFlow();

  const renderStatus = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
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
        onDataReplaced={replaceData}
        onChangeAction={resetAction}
      />
    );
  }

  if (data && action === QueenAction.MOVE_ENCHANTS) {
    return (
      <QueenMoveAffixesForm
        data={data}
        characterId={characterId}
        onDataReplaced={replaceData}
        onChangeAction={resetAction}
      />
    );
  }

  return (
    <CraftingActionLayout
      title="Queen of Hearts"
      status={renderStatus()}
      form={renderSelectionForm()}
      help_href="/information/random-enchants"
      help_label="Random enchant help"
    />
  );
};

export default QueenOfHeartsFlow;
