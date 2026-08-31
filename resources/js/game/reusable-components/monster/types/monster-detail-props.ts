import MonsterDetailDefinition from '../api/definitions/monster-detail-definition';

export interface MonsterNavigationDefinition {
  on_open_map?: (id: number) => void;
  on_open_item?: (id: number) => void;
}

export default interface MonsterDetailProps {
  monster: MonsterDetailDefinition;
  navigation?: MonsterNavigationDefinition;
}
