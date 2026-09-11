import UseOpenAllActiveGemScrollsSidePeekDefinition from './types/use-open-all-active-gem-scrolls-side-peek-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenAllActiveGemScrollsSidePeek =
  (): UseOpenAllActiveGemScrollsSidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openAllActiveGemScrolls = (characterId: number) => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.MAP_ACTIONS_ALL_ACTIVE_GEM_SCROLLS,
        {
          is_open: true,
          title: 'All Active Gem Scrolls',
          character_id: characterId,
        }
      );
    };

    return {
      openAllActiveGemScrolls,
    };
  };
