import CharacterGemDefinition from '../../../../../../../../../api-definitions/gems/character-gem-definition';

export interface ItemSocketDataDefinition {
  item_sockets: number;
  current_used_slots: number;
  item_name: string;
}

export interface GemReplacementDefinition {
  removed_gem: CharacterGemDefinition;
  added_gem: CharacterGemDefinition;
}

export default interface GemComparisonApiResponseDefinition {
  attached_gems: CharacterGemDefinition[];
  socket_data: ItemSocketDataDefinition;
  has_gems_on_item: boolean;
  removed_gem: CharacterGemDefinition | null;
  added_gem: CharacterGemDefinition;
  replacements: GemReplacementDefinition[];
  message?: string;
}
