import { useContext } from 'react';

import UseFactionLoyaltyStatusDefinition from '../api/hooks/definitions/use-faction-loyalty-status-definition';
import { FactionLoyaltyContext } from '../providers/faction-loyalty-provider';

export const useFactionLoyaltyContext =
  (): UseFactionLoyaltyStatusDefinition => {
    const context = useContext(FactionLoyaltyContext);

    if (context === undefined) {
      throw new Error(
        'useFactionLoyaltyContext must be used within a FactionLoyaltyProvider'
      );
    }

    return context;
  };
