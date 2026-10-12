import GuideQuestDefinition from '../../api/definitions/guide-quest-definition';
import { GuideQuestBatchCraftedItemRequirementProgressDefinition } from '../../api/definitions/guide-quest-response-definition';

export default interface GuideQuestRequirementsProps {
  quest: GuideQuestDefinition;
  completed_requirements: string[];
  batch_crafted_item_requirements: GuideQuestBatchCraftedItemRequirementProgressDefinition[];
}
