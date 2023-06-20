import ItemSkill from "./deffinitions/item-skill";
import ItemSkillProgression from "./deffinitions/item-skill-progression";

export default interface ItemSkillManagementProps {
    slot_id: number;
    skill_data: ItemSkill[];
    skill_progression_data: ItemSkillProgression[];
    close_skill_tree: () => void;
    character_id: number;
}