
import ItemSkill from "../types/deffinitions/item-skill";
import ItemSkillProgression from "../types/deffinitions/item-skill-progression";

/**
 * Determine if the skill is locked.
 *
 * @param skill
 * @param skillData
 * @param progressionData
 * @returns
 */
export const isSkillLocked = (skill: ItemSkill, skillData: ItemSkill[], progressionData: ItemSkillProgression[]): boolean => {

    const parentSkill: ItemSkill | undefined = findParentSkill(skill, skillData);
    let isLocked      = false;

    if (typeof parentSkill !== 'undefined') {
        const progressionDataForParent = getSkillProgressionData(parentSkill, progressionData);

        if (typeof progressionDataForParent !== 'undefined' && skill.parent_level_needed !== null) {
            isLocked = progressionDataForParent.current_level < skill.parent_level_needed;
        }
    }

    return isLocked;
}

/**
 * Get skill progression data:
 *
 * @param skill
 * @param progressionData
 * @returns
 */
export const getSkillProgressionData = (skill: ItemSkill, progressionData: ItemSkillProgression[]): ItemSkillProgression | undefined => {
    return progressionData.find((data: ItemSkillProgression) => data.item_skill_id === skill.id);
}

/**
 * Find the parent skill.
 *
 * @param skill
 * @param skills
 * @returns
 */
export const findParentSkill = (skill: ItemSkill, skills: ItemSkill[]): ItemSkill | undefined => {
    for (const skillData of skills) {

        if (skillData.id === skill.parent_id) {
            return skillData;
        }

        if (skillData.children.length > 0) {
            const parentSkill: ItemSkill | undefined = findParentSkill(skill, skillData.children);

            if (typeof parentSkill !== 'undefined') {
                return parentSkill
            }
        }
    }

    return undefined;
}
