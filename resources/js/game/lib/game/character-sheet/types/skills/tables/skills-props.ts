import SkillType from "../skill-type";

export default interface SkillsProps {

    trainable_skills: SkillType[] | [];

    dark_table: boolean;

    is_dead: boolean;
}
