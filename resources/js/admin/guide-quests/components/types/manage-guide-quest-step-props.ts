import GuideQuestDefinition from '../../api/definitions/guide-quest-definition';
import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-defintion';

export default interface ManageGuideQuestStepProcessProps {
  data_for_component: GuideQuestResponseDefinition;
  on_update: (formData: Partial<GuideQuestDefinition>) => void;
}
