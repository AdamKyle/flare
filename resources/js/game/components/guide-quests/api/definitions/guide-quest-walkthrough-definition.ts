import { GuideWalkthroughEvent } from '../../walkthrough/enums/guide-walkthrough-event';
import { GuideWalkthroughStepType } from '../../walkthrough/enums/guide-walkthrough-step-type';
import { GuideTarget } from '../../walkthrough/registry/guide-targets';

export interface GuideQuestWalkthroughStepDefinition {
  id: number;
  position: number;
  title: string;
  content: string;
  target: GuideTarget | null;
  step_type: GuideWalkthroughStepType;
  required_event: GuideWalkthroughEvent | null;
  optional_if_target_missing: boolean;
}

export default interface GuideQuestWalkthroughDefinition {
  guide_quest_id: number;
  current_step_id: number | null;
  walkthrough_completed: boolean;
  steps: GuideQuestWalkthroughStepDefinition[];
}
