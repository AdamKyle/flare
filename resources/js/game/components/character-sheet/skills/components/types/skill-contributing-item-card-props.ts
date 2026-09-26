import SkillItemContributionDefinition from '../../api/definitions/skill-item-contribution-definition';

export default interface SkillContributingItemCardProps {
  item: SkillItemContributionDefinition;
  on_open: (item: SkillItemContributionDefinition) => void;
}
