import { createContext } from 'react';

import { GuideWalkthroughEvent } from '../enums/guide-walkthrough-event';
import { GuideWalkthroughInteractionSignal } from '../enums/guide-walkthrough-interaction-signal';
import { GuideTarget } from '../registry/guide-targets';

export interface GuideWalkthroughContextDefinition {
  active_guide_quest_id: number | null;
  active_target: GuideTarget | null;
  loading: boolean;
  error: string | null;
  startWalkthrough: (guideQuestId: number) => Promise<boolean>;
  pauseWalkthrough: () => void;
  closeWalkthrough: () => void;
  completeTargetInteraction: (
    target: GuideTarget,
    signal: GuideWalkthroughInteractionSignal
  ) => void;
  completeRequiredEvent: (event: GuideWalkthroughEvent) => Promise<boolean>;
  registerTargetCleanup: (
    target: GuideTarget,
    cleanup: () => void
  ) => () => void;
}

export const GuideWalkthroughContext =
  createContext<GuideWalkthroughContextDefinition | null>(null);
