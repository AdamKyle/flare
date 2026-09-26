import SkillItemContributionDefinition from '../../api/definitions/skill-item-contribution-definition';

export default interface SkillItemsAffectingListProps {
  skill_id: number;
  items: SkillItemContributionDefinition[];
  on_open_item: (item: SkillItemContributionDefinition) => void;
}
