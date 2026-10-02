import { createContext } from 'react';

import UseGuideQuestsDefinition from '../api/hooks/definitions/use-guide-quests-definition';

export const GuideQuestContext = createContext<UseGuideQuestsDefinition | null>(
  null
);
