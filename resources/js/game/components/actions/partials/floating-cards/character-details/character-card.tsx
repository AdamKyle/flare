import React, { ReactNode, useEffect, useState } from 'react';

import CharacterActiveBoonsScreen from './alchemy-boons/character-active-boons-screen';
import CharacterCardDetails from './character-card-details';
import { useManageCharacterCardVisibility } from './hooks/use-manage-character-card-visibility';
import { formatNumberWithCommas } from '../../../../../util/format-number';
import { useOpenCharacterUsableInventory } from '../../../../character-sheet/partials/character-inventory/hooks/use-open-character-usable-inventory';
import FloatingCard from '../../../components/icon-section/floating-card';
import { useActiveBoonsApi } from '../crafting-section/sections/alchemy/active-boons/api/hooks/use-active-boons-api';
import { useActiveBoonsWebsocket } from '../crafting-section/sections/alchemy/active-boons/websockets/hooks/use-active-boons-websocket';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import FloatingCardScreenStack from 'ui/floating-card-screen-stack/floating-card-screen-stack';

const CharacterCard = (): ReactNode => {
  const { closeCharacterChard } = useManageCharacterCardVisibility();
  const { gameData } = useGameData();

  const characterData = gameData?.character;
  const characterId = characterData?.id ?? 0;
  const userId = characterData?.user_id ?? 0;

  const [showActiveBoons, setShowActiveBoons] = useState(false);

  const activeBoonsApi = useActiveBoonsApi({ characterId });

  useActiveBoonsWebsocket({ userId, onBoonsUpdated: activeBoonsApi.refresh });

  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id: characterId,
  });

  useEffect(() => {
    if (
      showActiveBoons &&
      !activeBoonsApi.loading &&
      activeBoonsApi.boons.length === 0
    ) {
      setShowActiveBoons(false);
    }
  }, [showActiveBoons, activeBoonsApi.loading, activeBoonsApi.boons.length]);

  if (!characterData) {
    return (
      <FloatingCard title="An error occured" close_action={closeCharacterChard}>
        <GameDataError />
      </FloatingCard>
    );
  }

  const handleOpenActiveBoons = (): void => setShowActiveBoons(true);
  const handleCloseActiveBoons = (): void => setShowActiveBoons(false);

  return (
    <FloatingCard
      title={
        characterData.name +
        ' (Level ' +
        formatNumberWithCommas(characterData.level) +
        '/' +
        formatNumberWithCommas(characterData.max_level) +
        ')'
      }
      close_action={closeCharacterChard}
      back_action={showActiveBoons ? handleCloseActiveBoons : undefined}
    >
      <FloatingCardScreenStack label="Character details">
        <div inert={showActiveBoons} aria-hidden={showActiveBoons}>
          <CharacterCardDetails
            characterData={characterData}
            active_boons={activeBoonsApi.boons}
            active_boons_loading={activeBoonsApi.loading}
            on_open_active_boons={handleOpenActiveBoons}
            on_active_boons_complete={activeBoonsApi.refresh}
          />
        </div>
        {showActiveBoons && (
          <CharacterActiveBoonsScreen
            boons={activeBoonsApi.boons}
            loading={activeBoonsApi.loading}
            error={activeBoonsApi.error}
            success_message={activeBoonsApi.successMessage}
            mutation_error={activeBoonsApi.mutationError}
            filling_boon_id={activeBoonsApi.fillingBoonId}
            removing_boon_id={activeBoonsApi.removingBoonId}
            on_view_source_item={(boon) =>
              openUsableInventory(boon.boon_applied)
            }
            on_fill_up={(boonId) => void activeBoonsApi.fillUpBoon(boonId)}
            on_remove={(boonId) => void activeBoonsApi.removeBoon(boonId)}
          />
        )}
      </FloatingCardScreenStack>
    </FloatingCard>
  );
};

export default CharacterCard;
