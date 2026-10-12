import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-definition';
import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-definition';

export default interface ManageGuideQuestWalkthroughProps {
  steps: GuideQuestWalkthroughStepDefinition[];
  form_options: GuideQuestResponseDefinition;
  on_change: (steps: GuideQuestWalkthroughStepDefinition[]) => void;
}
