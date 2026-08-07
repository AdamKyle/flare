import React, { ReactNode } from 'react';

import SeerActionSelection from './seer-action-selection';
import SeerAttachGemForm from './seer-attach-gem-form';
import SeerManageSocketsForm from './seer-manage-sockets-form';
import SeerRemoveGemsForm from './seer-remove-gems-form';
import CraftingActionButton from '../../../shared/components/crafting-action-button';
import CraftingActionLayout from '../../../shared/components/crafting-action-layout';
import { SeerAction } from '../enums/seer-action';
import { useSeerCampFlow } from '../hooks/use-seer-camp-flow';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
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
    if (!error) {
      return null;
    }

    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
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

  const renderChangeActionOnlyLayout = (form: ReactNode): ReactNode => (
    <CraftingActionLayout
      title="Seer Camp: Remove Gems"
      status={renderStatus()}
      form={form}
      action={
        <CraftingActionButton
          label="Change Action"
          on_click={changeAction}
          variant={ButtonVariant.PRIMARY}
        />
      }
      help_href="/information/seer-camp"
      help_label="Seer Camp help"
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
        status={status}
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
        status={status}
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
        status={status}
        onSuccess={handleActionSuccess}
        onChangeAction={changeAction}
      />
    );
  }

  return (
    <CraftingActionLayout
      title="Seer Camp"
      status={renderStatus()}
      form={renderSelectionForm()}
      help_href="/information/seer-camp"
      help_label="Seer Camp help"
    />
  );
};

export default SeerCampFlow;
