import React from 'react';

import GuideQuestProviderProps from './types/guide-quest-provider-props';
import { useGuideQuests } from '../api/hooks/use-guide-quests';
import { GuideQuestContext } from '../context/guide-quest-context';

import { useGameData } from 'game-data/hooks/use-game-data';

const GuideQuestProvider = ({ children }: GuideQuestProviderProps) => {
  const { gameData } = useGameData();
  const userId = gameData?.character?.user_id ?? 0;
  const guideQuestData = useGuideQuests(userId);

  return (
    <GuideQuestContext.Provider value={guideQuestData}>
      {children}
    </GuideQuestContext.Provider>
  );
};

export default GuideQuestProvider;
