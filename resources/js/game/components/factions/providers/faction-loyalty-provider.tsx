import React, { createContext, ReactNode } from 'react';

import FactionLoyaltyProviderProps from './types/faction-loyalty-provider-props';
import UseFactionLoyaltyStatusDefinition from '../api/hooks/definitions/use-faction-loyalty-status-definition';
import { useFactionLoyaltyStatus } from '../api/hooks/use-faction-loyalty-status';

import { useGameData } from 'game-data/hooks/use-game-data';

export const FactionLoyaltyContext = createContext<
  UseFactionLoyaltyStatusDefinition | undefined
>(undefined);

const FactionLoyaltyProvider = ({
  children,
}: FactionLoyaltyProviderProps): ReactNode => {
  const { gameData } = useGameData();
  const character = gameData?.character;

  const factionLoyaltyStatus = useFactionLoyaltyStatus({
    character_id: character?.id ?? 0,
    user_id: character?.user_id ?? 0,
    is_pledged: (character?.pledged_to_faction_id ?? null) !== null,
    initial_warning_notices: character?.faction_loyalty_warning_notices ?? [],
  });

  return (
    <FactionLoyaltyContext.Provider value={factionLoyaltyStatus}>
      {children}
    </FactionLoyaltyContext.Provider>
  );
};

export default FactionLoyaltyProvider;
