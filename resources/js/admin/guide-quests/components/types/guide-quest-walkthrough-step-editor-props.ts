import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-definition';
import GuideQuestResponseDefinition from '../../api/definitions/guide-quest-response-definition';

export default interface GuideQuestWalkthroughStepEditorProps {
  step: GuideQuestWalkthroughStepDefinition;
  index: number;
  step_type_options: GuideQuestResponseDefinition['walkthrough_step_type_options'];
  target_options: GuideQuestResponseDefinition['walkthrough_target_options'];
  event_options: GuideQuestResponseDefinition['walkthrough_event_options'];
  is_first: boolean;
  is_last: boolean;
  on_update: (changes: Partial<GuideQuestWalkthroughStepDefinition>) => void;
  on_move: (direction: -1 | 1) => void;
  on_remove: () => void;
}
