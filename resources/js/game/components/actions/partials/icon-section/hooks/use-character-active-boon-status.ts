import UseCharacterActiveBoonStatusDefinition from './definitions/use-character-active-boon-status-definition';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useCharacterActiveBoonStatus =
  (): UseCharacterActiveBoonStatusDefinition => {
    const { gameData } = useGameData();

    return {
      has_active_boons: (gameData?.character?.active_boons.length ?? 0) > 0,
    };
  };
