import ItemSkill from "../deffinitions/item-skill";
import ItemSkillProgression from "../deffinitions/item-skill-progression";

export default interface SkillTreeNodeProps {
    skill_progression: ItemSkillProgression;
    skill: ItemSkill;
    show_passive_modal: (skill: ItemSkillProgression | null) => void;
    is_locked: boolean;
}