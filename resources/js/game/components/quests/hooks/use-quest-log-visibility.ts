import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import UseQuestLogVisibilityDefinition from './definitions/use-quest-log-visibility-definition';
import { QuestLog } from '../event-types/quest-log';

export const useQuestLogVisibility = (): UseQuestLogVisibilityDefinition => {
  const eventSystem = useEventSystem();

  const [showQuestLog, setShowQuestLog] = useState<boolean>(false);

  const questLogVisibility = eventSystem.fetchOrCreateEventEmitter<{
    [key: string]: boolean;
  }>(QuestLog.OPEN_QUEST_LOG);

  useEffect(() => {
    const updateVisibility = (visible: boolean) => {
      setShowQuestLog(visible);
    };

    questLogVisibility.on(QuestLog.OPEN_QUEST_LOG, updateVisibility);

    return () => {
      questLogVisibility.off(QuestLog.OPEN_QUEST_LOG, updateVisibility);
    };
  }, [questLogVisibility]);

  return { showQuestLog };
};
