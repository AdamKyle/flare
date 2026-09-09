import MonsterDetailDefinition from '../api/definitions/monster-detail-definition';

export interface MonsterNavigationDefinition {
  on_open_map?: (id: number) => void;
  on_open_item?: (id: number) => void;
  on_open_map_gem?: (profile_id: number) => void;
  on_open_location_gem?: (profile_id: number) => void;
}

export default interface MonsterDetailProps {
  monster: MonsterDetailDefinition;
  navigation?: MonsterNavigationDefinition;
  initial_context_tab?: boolean;
  presentation?: 'page' | 'side-peek';
}
