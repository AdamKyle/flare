import { ElementalAtonementDefinition } from './gem-comparison-api-response-definition';

export interface SeerAttachedRemovalGemDefinition {
  gem_name: string;
  gem_id: number;
}

export interface SeerAtonementChangeDefinition {
  gem_id_to_remove: number;
  comparisons: ElementalAtonementDefinition;
}

export default interface SeerGemRemovalItemDefinition {
  slot_id: number;
  gems: SeerAttachedRemovalGemDefinition[];
  comparison: {
    original_atonement: ElementalAtonementDefinition;
    atonement_changes: SeerAtonementChangeDefinition[];
  };
  remove_one_cost: number;
  remove_all_cost: number;
}
