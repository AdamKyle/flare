import ItemSkillDefinition from '../../../../../../api-definitions/items/item-skill-definition';
import ItemSkillProgressionDefinition from '../../../../../../api-definitions/items/item-skill-progression-definition';
import ItemSkillTreeState from '../enums/item-skill-tree-state';

export default interface ItemSkillTreeNodeData {
  skill: ItemSkillDefinition;
  progression: ItemSkillProgressionDefinition;
  state: ItemSkillTreeState;
}
