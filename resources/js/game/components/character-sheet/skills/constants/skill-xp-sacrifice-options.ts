import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

/**
 * Mirrors the accepted values of `App\Game\Core\Rules\SkillXPPercentage`,
 * which validates `xp_percentage` when training a Skill.
 */
const SKILL_XP_SACRIFICE_PERCENTAGES: number[] = [
  0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 1,
];

export const skillXpSacrificeOptions: DropdownItem[] =
  SKILL_XP_SACRIFICE_PERCENTAGES.map((percentage) => ({
    label: `${Math.round(percentage * 100)}%`,
    value: percentage,
  }));
