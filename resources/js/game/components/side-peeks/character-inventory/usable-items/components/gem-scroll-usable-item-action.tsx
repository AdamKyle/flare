import React, { ReactNode } from 'react';

import GemScrollUsableItemActionProps from './types/gem-scroll-usable-item-action-props';
import { useGemScrollActions } from '../../../../../reusable-components/gems/progression/api/hooks/use-gem-scroll-actions';
import { useGemWorldContext } from '../../../../map-section/api/hooks/use-gem-world-context';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';
import LoadingButton from 'ui/buttons/loading-button';

const GemScrollUsableItemAction = ({
  item,
  character_id: characterId,
  on_activated: onActivated,
}: GemScrollUsableItemActionProps): ReactNode => {
  // game_map_id/x/y only gate when this hook refetches; they are never sent to
  // the server, so the Alchemy Bag (which has no map position of its own) can
  // safely pass stable values and get a single fetch on mount.
  const { data, loading, error } = useGemWorldContext({
    character_id: characterId,
    game_map_id: 0,
    x: 0,
    y: 0,
  });

  const {
    actingScrollId,
    successMessage,
    mutationError,
    useScroll: activateScroll,
  } = useGemScrollActions({ characterId, onSuccess: onActivated });

  if (item.slot_id === null) {
    return null;
  }

  const slotId = item.slot_id;
  const isBusy = actingScrollId === slotId;

  if (loading) {
    return (
      <div className="text-sm text-gray-600 dark:text-gray-400" role="status">
        Checking your Gem World status…
      </div>
    );
  }

  if (error) {
    return <Alert variant={AlertVariant.DANGER}>{error}</Alert>;
  }

  const renderMutationFeedback = (): ReactNode => {
    if (!mutationError && !successMessage) {
      return null;
    }

    return (
      <Alert
        variant={mutationError ? AlertVariant.DANGER : AlertVariant.SUCCESS}
      >
        {mutationError ?? successMessage}
      </Alert>
    );
  };

  const renderActivation = (): ReactNode => {
    if (!data?.inside_gem_world) {
      return (
        <Alert variant={AlertVariant.INFO}>
          This Gem Scroll can only be activated while you are inside a generated
          Gem World.
        </Alert>
      );
    }

    return (
      <LoadingButton
        label="Activate Scroll"
        loading_label="Activating Scroll…"
        variant={ButtonVariant.ALCHEMY}
        is_loading={isBusy}
        on_click={() => void activateScroll(slotId)}
      />
    );
  };

  return (
    <div className="flex flex-col gap-2">
      {renderMutationFeedback()}
      {renderActivation()}
    </div>
  );
};

export default GemScrollUsableItemAction;
