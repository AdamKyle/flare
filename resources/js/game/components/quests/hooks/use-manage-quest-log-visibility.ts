import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseManageQuestLogVisibilityDefinition from './definitions/use-manage-quest-log-visibility-definition';
import { ActionCardEvents } from '../../actions/partials/floating-cards/event-types/action-cards';
import { QuestLog } from '../event-types/quest-log';

export const useManageQuestLogVisibility =
  (): UseManageQuestLogVisibilityDefinition => {
    const eventSystem = useEventSystem();

    const manageQuestLogEmitter = eventSystem.fetchOrCreateEventEmitter<{
      [key: string]: boolean;
    }>(QuestLog.OPEN_QUEST_LOG);

    const openQuestLog = () => {
      const closeCharacterCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CHARACTER_CARD);

      const closeCraftingCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_CRATING_CARD);

      const closeMapCardEvent = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_MAP_SECTION);

      const closeShopCard = eventSystem.getEventEmitter<{
        [key: string]: boolean;
      }>(ActionCardEvents.OPEN_SHOP);

      closeCraftingCardEvent.emit(ActionCardEvents.OPEN_CRATING_CARD, false);

      closeCharacterCardEvent.emit(ActionCardEvents.OPEN_CHARACTER_CARD, false);

      closeMapCardEvent.emit(ActionCardEvents.OPEN_MAP_SECTION, false);

      closeShopCard.emit(ActionCardEvents.OPEN_SHOP, false);

      manageQuestLogEmitter.emit(QuestLog.OPEN_QUEST_LOG, true);
    };

    const closeQuestLog = () => {
      manageQuestLogEmitter.emit(QuestLog.OPEN_QUEST_LOG, false);
    };

    return { openQuestLog, closeQuestLog };
  };
