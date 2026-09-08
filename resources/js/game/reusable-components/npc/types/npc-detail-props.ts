import NpcFactualDefinition from './npc-factual-definition';

export interface NpcDetailNavigationDefinition {
  on_open_map?: (id: number) => void;
}

export default interface NpcDetailProps {
  npc: NpcFactualDefinition;
  navigation?: NpcDetailNavigationDefinition;
}
