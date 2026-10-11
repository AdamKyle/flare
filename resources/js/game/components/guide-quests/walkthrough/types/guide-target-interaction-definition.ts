import { Alignment, Side } from 'driver.js';

import { GuideWalkthroughInteraction } from '../enums/guide-walkthrough-interaction';
import { GuideWalkthroughInteractionSignal } from '../enums/guide-walkthrough-interaction-signal';

export default interface GuideTargetInteractionDefinition {
  interaction: GuideWalkthroughInteraction;
  completion_signal?: GuideWalkthroughInteractionSignal;
  popover_side?: Side;
  popover_align?: Alignment;
  cleanup_on_exit?: boolean;
  skip_when_target_missing?: boolean;
}
