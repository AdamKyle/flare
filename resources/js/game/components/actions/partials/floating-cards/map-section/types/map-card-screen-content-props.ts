import React from 'react';

import FactionDefinition from '../../../../../factions/api/definitions/faction-definition';
import { MapCardScreen } from '../enums/map-card-screen';

export default interface MapCardScreenContentProps {
  active_screen: MapCardScreen;
  character_id: number;
  selected_faction: FactionDefinition | null;
  selected_npc_id: number | null;
  map_content: React.ReactNode;
  on_open_faction: (faction: FactionDefinition) => void;
  on_faction_updated: (faction: FactionDefinition) => void;
  on_open_faction_loyalty: () => void;
  on_open_npc: (factionLoyaltyNpcId: number) => void;
}
