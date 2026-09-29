import { createContext } from 'react';

export interface GuideWalkthroughContextDefinition {
  active_guide_quest_id: number | null;
  loading: boolean;
  error: string | null;
  startWalkthrough: (guideQuestId: number) => Promise<boolean>;
  resumeWalkthrough: (guideQuestId: number) => Promise<boolean>;
  pauseWalkthrough: () => void;
  closeWalkthrough: () => void;
}

export const GuideWalkthroughContext =
  createContext<GuideWalkthroughContextDefinition | null>(null);
