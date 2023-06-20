import ItemSkillProgression from "./deffinitions/item-skill-progression";
import ItemSkill from "./deffinitions/item-skill";

export default interface ItemSkillManagementState {

    skill_progression: ItemSkillProgression | null;

    skill: ItemSkill | null;
}