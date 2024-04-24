import ItemSkill from "./deffinitions/item-skill";
import ItemSkillProgression from "./deffinitions/item-skill-progression";

export default interface ItemSkillDetailsProps {
    skill_progression_data: ItemSkillProgression;

    skills: ItemSkill[];

    manage_skill_details: (
        skill: ItemSkill | null,
        progession: ItemSkillProgression | null,
    ) => void;

    character_id: number;

    is_skill_locked: boolean;
}
