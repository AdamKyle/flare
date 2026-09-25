import ItemSkillDefinition from '../item-skill-definition';
import ItemSkillProgressionDefinition from '../item-skill-progression-definition';
import { EquippableItemWithBase } from './base-equippable-item-definition';

export type EquippableItemDetailsDefinition = EquippableItemWithBase & {
  is_equipped: boolean;
  can_manage_item_skills: boolean;
  item_skills: ItemSkillDefinition[];
  item_skill_progressions: ItemSkillProgressionDefinition[];
};
