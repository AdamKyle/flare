import { MapCardScreen } from '../enums/map-card-screen';

export default interface ResolveMapCardScreenTitleParams {
  active_screen: MapCardScreen;
  map_name: string;
  selected_faction_map_name: string | null;
  selected_npc_real_name: string | null;
}
