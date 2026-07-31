import SkillType from "../skill-type";

export default interface SkillsProps {
    trainable_skills: SkillType[] | [];

    character_id: number;

    update_skills: (
        skills: Partial<{
            training_skills: SkillType[];
            crafting_skills: SkillType[];
        }>,
    ) => void;

    dark_table: boolean;

    is_dead: boolean;

    is_automation_running: boolean;

    read_only?: boolean;
}
