import React, { ReactNode } from 'react';

import CharacterQuestHandInActionsProps from './types/character-quest-hand-in-actions-props';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

const CharacterQuestHandInActions = ({
  is_completed: isCompleted,
  readiness,
  handing_in: handingIn,
  success_message: successMessage,
  error_message: errorMessage,
  on_hand_in: onHandIn,
}: CharacterQuestHandInActionsProps): ReactNode => {
  const renderMutationAlert = (): ReactNode => {
    if (errorMessage) {
      return <Alert variant={AlertVariant.DANGER}>{errorMessage}</Alert>;
    }

    if (successMessage) {
      return <Alert variant={AlertVariant.SUCCESS}>{successMessage}</Alert>;
    }

    return null;
  };

  if (isCompleted) {
    return (
      <div className="flex flex-col gap-3">
        {renderMutationAlert()}
        <Alert variant={AlertVariant.SUCCESS}>Quest completed.</Alert>
      </div>
    );
  }

  const renderReadinessAlert = (): ReactNode => {
    if (readiness.can_hand_in || readiness.message === null) {
      return null;
    }

    return <Alert variant={AlertVariant.WARNING}>{readiness.message}</Alert>;
  };

  return (
    <div className="flex flex-col gap-3">
      {renderMutationAlert()}
      {renderReadinessAlert()}
      <div className="flex justify-end">
        <Button
          label="Hand In Quest"
          variant={ButtonVariant.PRIMARY}
          disabled={!readiness.can_hand_in || handingIn}
          aria_busy={handingIn}
          on_click={onHandIn}
        />
      </div>
    </div>
  );
};

export default CharacterQuestHandInActions;
