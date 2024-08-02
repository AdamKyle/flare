import SkillType from "../deffinitions/skill-type";

export default interface CharacterSkillTabsState {
    loading: boolean;

    skills: {
        crafting_skills: SkillType[] | [];
        training_skills: SkillType[] | [];
    } | null;

    dark_tables: boolean;
}
