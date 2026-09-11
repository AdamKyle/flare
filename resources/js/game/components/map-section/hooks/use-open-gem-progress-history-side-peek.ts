import UseOpenGemProgressHistorySidePeekDefinition from './types/use-open-gem-progress-history-side-peek-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenGemProgressHistorySidePeek =
  (): UseOpenGemProgressHistorySidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openGemProgressHistory = (characterId: number) => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.MAP_ACTIONS_GEM_PROGRESS_HISTORY,
        {
          is_open: true,
          title: 'Gem Progress History',
          character_id: characterId,
        }
      );
    };

    return {
      openGemProgressHistory,
    };
  };
