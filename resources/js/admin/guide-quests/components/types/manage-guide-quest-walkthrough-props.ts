import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-definition';
import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-defintion';

export default interface ManageGuideQuestWalkthroughProps {
  data: GuideQuestResponseDefinition;
  on_update: (steps: GuideQuestWalkthroughStepDefinition[]) => void;
}
