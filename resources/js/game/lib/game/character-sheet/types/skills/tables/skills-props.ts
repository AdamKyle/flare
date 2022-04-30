import SkillType from "../skill-type";

export default interface SkillsProps {

    trainable_skills: SkillType[] | [];

    character_id: number,

    update_skills: (skills: any) => void;

    dark_table: boolean;

    is_dead: boolean;
}
