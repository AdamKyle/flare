import React, { ReactNode } from 'react';

import ActiveBoonsContent from './active-boons-content';
import { useOpenCharacterUsableInventory } from '../../../../../../../../character-sheet/partials/character-inventory/hooks/use-open-character-usable-inventory';
import { useActiveBoonsActions } from '../api/hooks/use-active-boons-actions';

import { useGameData } from 'game-data/hooks/use-game-data';

const ActiveBoons = (): ReactNode => {
  const { gameData } = useGameData();

  const characterId = gameData?.character?.id ?? 0;
  const boons = gameData?.character?.active_boons ?? [];

  const {
    successMessage,
    mutationError,
    fillingBoonId,
    removingBoonId,
    fillUpBoon,
    removeBoon,
  } = useActiveBoonsActions({ characterId });

  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id: characterId,
  });

  return (
    <ActiveBoonsContent
      boons={boons}
      loading={false}
      error={null}
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
