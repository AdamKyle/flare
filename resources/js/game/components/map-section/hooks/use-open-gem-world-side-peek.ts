import UseOpenGemWorldSidePeekDefinition from './types/use-open-gem-world-side-peek-definition';
import AreaGemContextDefinition from '../../../reusable-components/gems/api/definitions/area-gem-context-definition';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenGemWorldSidePeek =
  (): UseOpenGemWorldSidePeekDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openGemWorld = (
      characterId: number,
      context: AreaGemContextDefinition,
      canEnter: boolean
    ) => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.MAP_ACTIONS_GEM_WORLD,
        {
          is_open: true,
          title: 'Gem Effects',
          character_id: characterId,
          context,
          can_enter: canEnter,
        }
      );
    };

    return {
      openGemWorld,
    };
  };
