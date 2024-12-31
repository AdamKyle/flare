import { useEventSystem } from 'event-system/hooks/use-event-system';

import UseManageGameLoaderVisibility from './definitions/use-manage-game-loader-visibility';
import { GameLoaderEvents } from '../game-loader/event-types/game-loader-events';

export const useManageGameLoaderVisibility =
  (): UseManageGameLoaderVisibility => {
    const eventSystem = useEventSystem();

    const manageGameLoaderEmitter = eventSystem.getEventEmitter<{
      [key: string]: boolean;
    }>(GameLoaderEvents.SHOW_GAME_LOADER);

    const hideGameLoader = () => {
      manageGameLoaderEmitter.emit(GameLoaderEvents.SHOW_GAME_LOADER, false);
    };

    return {
      hideGameLoader,
    };
  };
