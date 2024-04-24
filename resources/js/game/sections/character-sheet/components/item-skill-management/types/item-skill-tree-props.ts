import ItemSkill from "./deffinitions/item-skill";
import ItemSkillProgression from "./deffinitions/item-skill-progression";

export default interface ItemSkillTreeProps {
    skill_data: ItemSkill[];
    progression_data: ItemSkillProgression[];
    show_skill_management: (
        skill: ItemSkill,
        progression: ItemSkillProgression | null,
    ) => void;
}
