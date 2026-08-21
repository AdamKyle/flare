import { CraftSetPosition } from '../../enums/craft-set-position';
import { CraftingSkillGroup } from '../../enums/crafting-skill-group';

export default interface CraftSetRecommendationPositionDefinition {
  position: CraftSetPosition;
  item_id: number;
  crafting_type: CraftingSkillGroup;
  item_name: string;
}
