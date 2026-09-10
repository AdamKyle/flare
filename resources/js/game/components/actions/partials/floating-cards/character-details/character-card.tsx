import React, { ReactNode, useEffect, useState } from 'react';

import CharacterActiveBoonsScreen from './alchemy-boons/character-active-boons-screen';
import CharacterCardDetails from './character-card-details';
import { useManageCharacterCardVisibility } from './hooks/use-manage-character-card-visibility';
import { formatNumberWithCommas } from '../../../../../util/format-number';
import { useOpenCharacterUsableInventory } from '../../../../character-sheet/partials/character-inventory/hooks/use-open-character-usable-inventory';
import FloatingCard from '../../../components/icon-section/floating-card';
import { useActiveBoonsActions } from '../crafting-section/sections/alchemy/active-boons/api/hooks/use-active-boons-actions';

import { GameDataError } from 'game-data/components/game-data-error';
import { useGameData } from 'game-data/hooks/use-game-data';

import FloatingCardScreenStack from 'ui/floating-card-screen-stack/floating-card-screen-stack';

const CharacterCard = (): ReactNode => {
  const { closeCharacterChard } = useManageCharacterCardVisibility();
  const { gameData } = useGameData();

  const characterData = gameData?.character;
  const characterId = characterData?.id ?? 0;
  const activeBoons = characterData?.active_boons ?? [];

  const [showActiveBoons, setShowActiveBoons] = useState(false);

  const activeBoonsActions = useActiveBoonsActions({ characterId });

  const { openUsableInventory } = useOpenCharacterUsableInventory({
    character_id: characterId,
  });

  useEffect(() => {
    if (showActiveBoons && activeBoons.length === 0) {
      setShowActiveBoons(false);
    }
  }, [showActiveBoons, activeBoons.length]);

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
            active_boons={activeBoons}
            on_open_active_boons={handleOpenActiveBoons}
          />
        </div>
        {showActiveBoons && (
          <CharacterActiveBoonsScreen
            boons={activeBoons}
            loading={false}
            error={null}
            success_message={activeBoonsActions.successMessage}
            mutation_error={activeBoonsActions.mutationError}
            filling_boon_id={activeBoonsActions.fillingBoonId}
            removing_boon_id={activeBoonsActions.removingBoonId}
            on_view_source_item={(boon) =>
              openUsableInventory(boon.boon_applied)
            }
            on_fill_up={(boonId) => void activeBoonsActions.fillUpBoon(boonId)}
            on_remove={(boonId) => void activeBoonsActions.removeBoon(boonId)}
          />
        )}
      </FloatingCardScreenStack>
    </FloatingCard>
  );
};

export default CharacterCard;
