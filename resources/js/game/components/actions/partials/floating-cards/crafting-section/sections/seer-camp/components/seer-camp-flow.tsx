import React, { ReactNode } from 'react';

import SeerActionSelection from './seer-action-selection';
import SeerAttachGemForm from './seer-attach-gem-form';
import SeerManageSocketsForm from './seer-manage-sockets-form';
import SeerRemoveGemsForm from './seer-remove-gems-form';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
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
    if (data) {
      return <SeerActionSelection onSelect={selectAction} />;
    }

    if (error) {
      return null;
    }

    return (
      <Alert variant={AlertVariant.INFO}>
        Unable to load the Seer Camp right now.
      </Alert>
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

  const renderChangeActionOnlyLayout = (form: ReactNode): ReactNode => (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Seer Camp: Remove Gems
        </h2>
      }
      status={renderStatus()}
      form={form}
      action={
        <Button
          label="Change Action"
          on_click={changeAction}
          variant={ButtonVariant.PRIMARY}
        />
      }
      help_link={renderHelpLink()}
    />
  );

  if (loading) {
    return (
      <IndeterminateProgressBar
        label="Loading Seer Camp"
        variant={ProgressBarVariant.PRIMARY}
      />
    );
  }

  if (data && action === SeerAction.MANAGE_SOCKETS) {
    return (
      <SeerManageSocketsForm
        costs={data.costs}
        characterId={characterId}
        rootStatus={renderStatus()}
        helpLink={renderHelpLink()}
        onSuccess={handleActionSuccess}
        onChangeAction={changeAction}
      />
    );
  }

  if (data && action === SeerAction.ATTACH_GEM) {
    return (
      <SeerAttachGemForm
        costs={data.costs}
        characterId={characterId}
        rootStatus={renderStatus()}
        helpLink={renderHelpLink()}
        onSuccess={handleActionSuccess}
        onChangeAction={changeAction}
      />
    );
  }

  if (action === SeerAction.REMOVE_GEM) {
    if (removalError) {
      return renderChangeActionOnlyLayout(
        <Alert variant={AlertVariant.DANGER}>{removalError}</Alert>
      );
    }

    if (removalLoading || !data) {
      return (
        <IndeterminateProgressBar
          label="Loading Gems available to remove"
          variant={ProgressBarVariant.PRIMARY}
        />
      );
    }

    return (
      <SeerRemoveGemsForm
        removalData={data.removal_data}
        characterId={characterId}
        rootStatus={renderStatus()}
        helpLink={renderHelpLink()}
        onSuccess={handleActionSuccess}
        onChangeAction={changeAction}
      />
    );
  }

  return (
    <CraftingActionLayout
      heading={
        <h2 className="text-xl font-semibold text-gray-900 dark:text-gray-100">
          Seer Camp
        </h2>
      }
      status={renderStatus()}
      form={renderSelectionForm()}
      help_link={renderHelpLink()}
    />
  );
};

export default SeerCampFlow;
