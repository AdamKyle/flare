import React, { ReactNode } from 'react';

import ActiveBoonsContent from './active-boons-content';
import { useOpenCharacterUsableInventory } from '../../../../../../../../character-sheet/partials/character-inventory/hooks/use-open-character-usable-inventory';
import { useActiveBoonsApi } from '../api/hooks/use-active-boons-api';
import { useActiveBoonsWebsocket } from '../websockets/hooks/use-active-boons-websocket';

import { useGameData } from 'game-data/hooks/use-game-data';

const ActiveBoons = (): ReactNode => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;
  const userId = gameData?.character?.user_id ?? 0;

  const {
    boons,
    loading,
    error,
    successMessage,
    mutationError,
    fillingBoonId,
    removingBoonId,
    refresh,
    fillUpBoon,
    removeBoon,
  } = useActiveBoonsApi({ characterId });

  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id: characterId,
  });

  useActiveBoonsWebsocket({ userId, onBoonsUpdated: refresh });

  return (
    <ActiveBoonsContent
      boons={boons}
      loading={loading}
      error={error}
      success_message={successMessage}
      mutation_error={mutationError}
      filling_boon_id={fillingBoonId}
      removing_boon_id={removingBoonId}
      on_view_source_item={(boon) => openUsableInventory(boon.boon_applied)}
      on_fill_up={(boonId) => void fillUpBoon(boonId)}
      on_remove={(boonId) => void removeBoon(boonId)}
    />
  );
};

export default ActiveBoons;
