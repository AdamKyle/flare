import React, { ReactNode } from 'react';

import SeerActionSelection from './seer-action-selection';
import SeerAttachGemForm from './seer-attach-gem-form';
import SeerManageSocketsForm from './seer-manage-sockets-form';
import SeerRemoveGemsForm from './seer-remove-gems-form';
import { SeerAction } from '../enums/seer-action';
import { useSeerCampFlow } from '../hooks/use-seer-camp-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import { ProgressBarVariant } from 'ui/progress/enums/progress-bar-variant';
import IndeterminateProgressBar from 'ui/progress/indeterminate-progress-bar';

const SeerCampFlow = (): ReactNode => {
  const {
    characterId,
    data,
    loading,
    error,
    removalLoading,
    removalError,
    action,
    status,
    selectAction,
    changeAction,
    handleActionSuccess,
  } = useSeerCampFlow();

  const renderErrors = (): ReactNode => {
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  };

  const renderStatus = (): ReactNode => {
    if (!status) {
      return null;
    }

    return <Alert variant={AlertVariant.INFO}>{status}</Alert>;
  };

  const renderActionSelection = (): ReactNode => {
    if (action) {
      return null;
    }

    return <SeerActionSelection onSelect={selectAction} />;
  };

  const renderManageSocketsAction = (): ReactNode => {
    if (!data) {
      return null;
    }

    return (
      <SeerManageSocketsForm
        items={data.items}
        costs={data.costs}
        characterId={characterId}
        onSuccess={handleActionSuccess}
      />
    );
  };

  const renderAttachGemAction = (): ReactNode => {
    if (!data) {
      return null;
    }

    return (
      <SeerAttachGemForm
        items={data.items}
        gems={data.gems}
        costs={data.costs}
        characterId={characterId}
        onSuccess={handleActionSuccess}
      />
    );
  };

  const renderRemoveGemsAction = (): ReactNode => {
    if (removalError) {
      return <Alert variant={AlertVariant.DANGER}>{removalError}</Alert>;
    }

    if (removalLoading) {
      return (
        <IndeterminateProgressBar
          label="Loading Gems available to remove"
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    if (!data) {
      return null;
    }

    return (
      <SeerRemoveGemsForm
        removalData={data.removal_data}
        characterId={characterId}
        onSuccess={handleActionSuccess}
      />
    );
  };

  const renderCurrentAction = (): ReactNode => {
    if (!action) {
      return null;
    }

    if (action === SeerAction.MANAGE_SOCKETS) {
      return renderManageSocketsAction();
    }

    if (action === SeerAction.ATTACH_GEM) {
      return renderAttachGemAction();
    }

    return renderRemoveGemsAction();
  };

  const renderChangeActionButton = (): ReactNode => {
    if (!action) {
      return null;
    }

    return (
      <Button
        label="Change Action"
        on_click={changeAction}
        variant={ButtonVariant.PRIMARY}
      />
    );
  };

  const renderHelpLink = (): ReactNode => (
    <a
      href="/information/seer-camp"
      target="_blank"
      rel="noopener noreferrer"
      className="text-danube-700 focus:ring-danube-500 dark:text-danube-300 font-semibold underline focus:ring-2 focus:outline-none"
    >
      Seer Camp help (opens in a new tab)
    </a>
  );

  if (loading) {
    return (
      <IndeterminateProgressBar
        label="Loading Seer Camp"
        variant={ProgressBarVariant.PRIMARY}
      />
    );
  }

  return (
    <div className="space-y-4 text-gray-900 dark:text-gray-100">
      <h2 className="text-xl font-semibold">Seer Camp</h2>

      {renderErrors()}
      {renderStatus()}
      {renderActionSelection()}
      {renderCurrentAction()}
      {renderChangeActionButton()}
      {renderHelpLink()}
    </div>
  );
};

export default SeerCampFlow;
