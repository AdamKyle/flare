import { GuideQuestWalkthroughStepDefinition } from '../../api/definitions/guide-quest-walkthrough-definition';

export default interface PresentGuideStepParams {
  step: GuideQuestWalkthroughStepDefinition;
  element: HTMLElement | null;
  on_continue: () => void;
  on_close: () => void;
}
