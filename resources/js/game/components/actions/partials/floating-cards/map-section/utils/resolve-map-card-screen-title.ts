import { MapCardScreen } from '../enums/map-card-screen';
import ResolveMapCardScreenTitleParams from '../types/resolve-map-card-screen-title-params';

export const resolveMapCardScreenTitle = ({
  active_screen: activeScreen,
  map_name: mapName,
  selected_faction_map_name: selectedFactionMapName,
  selected_npc_real_name: selectedNpcRealName,
}: ResolveMapCardScreenTitleParams): string => {
  if (activeScreen === MapCardScreen.FACTIONS) {
    return 'Factions';
  }

  if (activeScreen === MapCardScreen.FACTION_DETAIL) {
    return `${selectedFactionMapName ?? ''} Faction`;
  }

  if (activeScreen === MapCardScreen.FACTION_LOYALTY) {
    return 'Faction Loyalty';
  }

  if (activeScreen === MapCardScreen.FACTION_LOYALTY_NPC) {
    return selectedNpcRealName ?? 'Faction Loyalty NPC';
  }

  return `Map: ${mapName}`;
};
