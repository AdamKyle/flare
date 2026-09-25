import ItemSkillDefinition from '../../../../../../api-definitions/items/item-skill-definition';
import ItemSkillProgressionDefinition from '../../../../../../api-definitions/items/item-skill-progression-definition';
import ItemSkillTreeState from '../enums/item-skill-tree-state';

export const flattenItemSkills = (
  skills: ItemSkillDefinition[]
): ItemSkillDefinition[] =>
  skills.flatMap((skill) => [skill, ...flattenItemSkills(skill.children)]);

export const resolveItemSkillState = (
  skill: ItemSkillDefinition,
  skills: ItemSkillDefinition[],
  progressions: ItemSkillProgressionDefinition[]
): ItemSkillTreeState => {
  const progression = progressions.find(
    (candidate) => candidate.item_skill_id === skill.id
  );

  if (progression && progression.current_level >= skill.max_level) {
    return ItemSkillTreeState.MAXED;
  }

  if (progression?.is_training === true) {
    return ItemSkillTreeState.TRAINING;
  }

  if (skill.parent_id === null) {
    return ItemSkillTreeState.AVAILABLE;
  }

  const parent = flattenItemSkills(skills).find(
    (candidate) => candidate.id === skill.parent_id
  );
  const parentProgression = progressions.find(
    (candidate) => candidate.item_skill_id === skill.parent_id
  );

  if (!parent || !parentProgression) {
    return ItemSkillTreeState.LOCKED;
  }

  if (skill.parent_level_needed === null) {
    return ItemSkillTreeState.AVAILABLE;
  }

  return parentProgression.current_level < skill.parent_level_needed
    ? ItemSkillTreeState.LOCKED
    : ItemSkillTreeState.AVAILABLE;
};
