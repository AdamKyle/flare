import { InventoryItemTypes } from '../../../partials/character-inventory/enums/inventory-item-types';
import { SkillBonusItemSource } from '../../enums/skill-bonus-item-source';

export default interface SkillItemContributionDefinition {
  item_id: number;
  slot_id: number;
  source: SkillBonusItemSource;
  name: string;
  type: InventoryItemTypes;
  position: string | null;
  affix_count: number;
  is_unique: boolean;
  is_mythic: boolean;
  is_cosmic: boolean;
  holy_stacks_applied: number;
  skill_bonus: number;
  skill_training_bonus: number;
}
