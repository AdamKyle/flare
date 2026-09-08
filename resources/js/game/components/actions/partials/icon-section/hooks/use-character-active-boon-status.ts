import UseCharacterActiveBoonStatusDefinition from './definitions/use-character-active-boon-status-definition';
import { useActiveBoonsApi } from '../../floating-cards/crafting-section/sections/alchemy/active-boons/api/hooks/use-active-boons-api';
import { useActiveBoonsWebsocket } from '../../floating-cards/crafting-section/sections/alchemy/active-boons/websockets/hooks/use-active-boons-websocket';

import { useGameData } from 'game-data/hooks/use-game-data';

export const useCharacterActiveBoonStatus =
  (): UseCharacterActiveBoonStatusDefinition => {
    const { gameData } = useGameData();

    const characterId = gameData?.character?.id ?? 0;
    const userId = gameData?.character?.user_id ?? 0;

    const { boons, refresh } = useActiveBoonsApi({ characterId });

    useActiveBoonsWebsocket({ userId, onBoonsUpdated: refresh });

    return {
      has_active_boons: boons.length > 0,
    };
  };
