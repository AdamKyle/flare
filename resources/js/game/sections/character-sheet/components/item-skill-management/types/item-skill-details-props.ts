import ItemSkillProgression from "./deffinitions/item-skill-progression";

export default interface ItemSkillDetailsProps {

    skill_progression_data: ItemSkillProgression;

    manage_skill_details: (skill: ItemSkillProgression | null) => void;
}