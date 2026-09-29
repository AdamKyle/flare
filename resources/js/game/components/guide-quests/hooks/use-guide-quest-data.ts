import { useContext } from 'react';

import { GuideQuestContext } from '../context/guide-quest-context';

export const useGuideQuestData = () => {
  const context = useContext(GuideQuestContext);

  if (!context) {
    throw new Error('useGuideQuestData must be used within GuideQuestProvider');
  }

  return context;
};
