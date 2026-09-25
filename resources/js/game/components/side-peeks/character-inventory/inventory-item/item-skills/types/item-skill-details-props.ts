import ItemSkillDefinition from '../../../../../../api-definitions/items/item-skill-definition';
import ItemSkillProgressionDefinition from '../../../../../../api-definitions/items/item-skill-progression-definition';
import ItemSkillTreeState from '../enums/item-skill-tree-state';

export default interface ItemSkillDetailsProps {
  skill: ItemSkillDefinition;
  progression: ItemSkillProgressionDefinition;
  state: ItemSkillTreeState;
  parent: ItemSkillDefinition | null;
  on_open_parent: (() => void) | null;
  can_manage_item_skills: boolean;
  processing: boolean;
  error_message: string | null;
  success_message: string | null;
  on_train: () => void;
  on_stop: () => void;
}
