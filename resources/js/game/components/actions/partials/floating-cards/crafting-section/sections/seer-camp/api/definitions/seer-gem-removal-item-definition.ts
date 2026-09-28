import CharacterGemDefinition from '../../../../../../../../../api-definitions/gems/character-gem-definition';

export type SeerAtonementChangeDefinition = CharacterGemDefinition;

export interface SeerAttachedRemovalGemDefinition {
  gem_name: string;
  gem_id: number;
}

export default interface SeerGemRemovalItemDefinition {
  slot_id: number;
  gems: SeerAttachedRemovalGemDefinition[];
  comparison: {
    removed_gems: CharacterGemDefinition[];
  };
  remove_one_cost: number;
  remove_all_cost: number;
}
