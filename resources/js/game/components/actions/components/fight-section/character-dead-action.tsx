import React, { ReactNode } from 'react';

import CharacterDeadActionProps from './types/character-dead-action-props';
import { useReviveCharacter } from '../../partials/monster-section/api/hooks/use-revive-character';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

/**
 * The Character's dead-state fight action: a clear death message and the
 * single primary action to revive through the existing backend revive
 * system. No Attack/Attack Again/Reset Fight action is available while dead.
 */
const CharacterDeadAction = ({
  character_id: characterId,
}: CharacterDeadActionProps): ReactNode => {
  const { loading, error, revive } = useReviveCharacter(characterId);

  return (
    <div className="my-4 flex w-full flex-col items-center gap-2 text-center">
      <p className="font-semibold text-rose-600 dark:text-rose-400">
        Your Character has died.
      </p>
      {error && <Alert variant={AlertVariant.DANGER}>{error}</Alert>}
      <LoadingButton
        label="Revive Character"
        loading_label="Reviving…"
        variant={ButtonVariant.DANGER}
        is_loading={loading}
        on_click={() => void revive()}
        additional_css="w-full lg:w-1/3"
      />
    </div>
  );
};

export default CharacterDeadAction;
