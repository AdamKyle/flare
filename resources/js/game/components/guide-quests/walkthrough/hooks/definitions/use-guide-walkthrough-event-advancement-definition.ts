import { GuideWalkthroughEvent } from '../../enums/guide-walkthrough-event';

export default interface UseGuideWalkthroughEventAdvancementDefinition {
  bindStepEvent: (event: GuideWalkthroughEvent, onEvent: () => void) => void;
  unbindStepEvent: () => void;
}
