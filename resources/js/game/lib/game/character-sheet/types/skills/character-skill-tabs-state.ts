import SkillType from "./skill-type";

export default interface CharacterSkillTabsState {

    loading: boolean;

    skills: {
        crafting_skills: SkillType[] | [];
        training_skills: SkillType[] | [];
    } | null;

    dark_tables: boolean;
}
