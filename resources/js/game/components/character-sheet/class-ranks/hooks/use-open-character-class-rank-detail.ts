import UseOpenCharacterClassRankDetailDefinition from './definitions/use-open-character-class-rank-detail-definition';
import { SidePeekComponentRegistrationEnum } from '../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterClassRankDetail =
  (): UseOpenCharacterClassRankDetailDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openCharacterClassRankDetail = (
      characterId: number,
      gameClassId: number,
      className: string
    ): void => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.CHARACTER_CLASS_RANK_DETAIL,
        {
          is_open: true,
          title: className,
          allow_clicking_outside: true,
          character_id: characterId,
          game_class_id: gameClassId,
        }
      );
    };

    return { openCharacterClassRankDetail };
  };
