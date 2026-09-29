import { createContext } from 'react';

import { useGuideQuests } from '../api/hooks/use-guide-quests';

export const GuideQuestContext = createContext<ReturnType<
  typeof useGuideQuests
> | null>(null);
