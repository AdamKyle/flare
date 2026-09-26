import React, { ReactNode } from 'react';

import { MapCardScreen } from './enums/map-card-screen';
import MapCardScreenContentProps from './types/map-card-screen-content-props';
import FactionDetailScreen from '../../../../factions/components/faction-detail-screen';
import FactionLoyaltyNpcScreen from '../../../../factions/components/faction-loyalty-npc-screen';
import FactionLoyaltyScreen from '../../../../factions/components/faction-loyalty-screen';
import FactionsScreen from '../../../../factions/components/factions-screen';

const MapCardScreenContent = ({
  active_screen: activeScreen,
  character_id: characterId,
  selected_faction: selectedFaction,
  selected_npc_id: selectedNpcId,
  map_content: mapContent,
  on_open_faction: onOpenFaction,
  on_faction_updated: onFactionUpdated,
  on_open_faction_loyalty: onOpenFactionLoyalty,
  on_open_npc: onOpenNpc,
}: MapCardScreenContentProps): ReactNode => {
  if (activeScreen === MapCardScreen.FACTIONS) {
    return (
      <FactionsScreen
        character_id={characterId}
        on_open_faction={onOpenFaction}
      />
    );
  }

  if (activeScreen === MapCardScreen.FACTION_DETAIL && selectedFaction) {
    return (
      <FactionDetailScreen
        character_id={characterId}
        faction={selectedFaction}
        on_faction_updated={onFactionUpdated}
        on_open_faction_loyalty={onOpenFactionLoyalty}
      />
    );
  }

  if (activeScreen === MapCardScreen.FACTION_LOYALTY) {
    return (
      <FactionLoyaltyScreen
        character_id={characterId}
        on_open_npc={onOpenNpc}
      />
    );
  }

  if (
    activeScreen === MapCardScreen.FACTION_LOYALTY_NPC &&
    selectedNpcId !== null
  ) {
    return (
      <FactionLoyaltyNpcScreen
        character_id={characterId}
        faction_loyalty_npc_id={selectedNpcId}
      />
    );
  }

  return mapContent;
};

export default MapCardScreenContent;
