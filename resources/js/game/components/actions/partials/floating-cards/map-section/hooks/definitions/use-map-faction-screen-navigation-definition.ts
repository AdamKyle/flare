import FactionDefinition from '../../../../../../factions/api/definitions/faction-definition';
import { MapCardScreen } from '../../enums/map-card-screen';

export default interface UseMapFactionScreenNavigationDefinition {
  active_screen: MapCardScreen;
  selected_faction: FactionDefinition | null;
  selected_npc_id: number | null;
  open_factions: () => void;
  open_faction: (faction: FactionDefinition) => void;
  update_selected_faction: (faction: FactionDefinition) => void;
  open_faction_loyalty: () => void;
  open_npc: (factionLoyaltyNpcId: number) => void;
  go_back: () => void;
}
