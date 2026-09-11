import UseOpenGemWorldIntroductionSidePeekDefinition from './types/use-open-gem-world-introduction-side-peek-definition';
import AreaGemContextDefinition from '../../../reusable-components/gems/api/definitions/area-gem-context-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenGemWorldIntroductionSidePeek =
  (): UseOpenGemWorldIntroductionSidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openGemWorldIntroduction = (
      characterId: number,
      context: AreaGemContextDefinition,
      canEnter: boolean
    ) => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.MAP_ACTIONS_GEM_WORLD_INTRODUCTION,
        {
          is_open: true,
          title: 'Gem World',
          character_id: characterId,
          entry_context: context,
          entry_can_enter: canEnter,
        }
      );
    };

    return {
      openGemWorldIntroduction,
    };
  };
