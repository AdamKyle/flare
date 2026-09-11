import { SidePeekComponentRegistrationEnum } from '../../../../components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../components/side-peeks/base/hooks/use-side-peek-emitter';
import UseOpenGemProgressionSidePeekDefinition from '../types/use-open-gem-progression-side-peek-definition';

export const useOpenGemProgressionSidePeek =
  (): UseOpenGemProgressionSidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openGemProgression = (characterId: number): void => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.MAP_ACTIONS_GEM_PROGRESSION,
        {
          is_open: true,
          title: 'Gem Progress',
          character_id: characterId,
        }
      );
    };

    return { openGemProgression };
  };
