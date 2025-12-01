import { useEventSystem } from 'event-system/hooks/use-event-system';
import { useEffect, useState } from 'react';

import { GuideQuestEventTypes } from '../event-types/guide-quest-event-types';
import UseManageGuideQuestsVisibilityDefinition from './definitions/use-manage-guide-quests-visibility-definition';

export const useManageGuideQuestsVisibility =
  (): UseManageGuideQuestsVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const [showGuideQuests, setShowGuideQuests] = useState(false);

    const manageInventoryEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(GuideQuestEventTypes.OPEN_GUIDE_QUEST);

    useEffect(() => {
      const updateVisibility = (visible: boolean) => {
        setShowGuideQuests(visible);
      };

      manageInventoryEmitter.on(
        GuideQuestEventTypes.OPEN_GUIDE_QUEST,
        updateVisibility
      );

      return () => {
        manageInventoryEmitter.off(
          GuideQuestEventTypes.OPEN_GUIDE_QUEST,
          updateVisibility
        );
      };
    }, [manageInventoryEmitter]);

    const openGuideQuestsScreen = () => {
      manageInventoryEmitter.emit(GuideQuestEventTypes.OPEN_GUIDE_QUEST, true);
    };

    const closeGuideQuestsScreen = () => {
      manageInventoryEmitter.emit(GuideQuestEventTypes.OPEN_GUIDE_QUEST, false);
    };

    return {
      showGuideQuests,
      openGuideQuestsScreen,
      closeGuideQuestsScreen,
    };
  };
