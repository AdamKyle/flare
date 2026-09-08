import UseOpenCharacterClassSpecialtyDetailDefinition from './definitions/use-open-character-class-specialty-detail-definition';
import { SidePeekComponentRegistrationEnum } from '../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterClassSpecialtyDetail =
  (): UseOpenCharacterClassSpecialtyDetailDefinition => {
    const sidePeekEmitter = useSidePeekEmitter();

    const openCharacterClassSpecialtyDetail = (
      characterId: number,
      gameClassSpecialId: number,
      specialtyName: string
    ): void => {
      sidePeekEmitter.emit(
        SidePeek.SIDE_PEEK,
        SidePeekComponentRegistrationEnum.CHARACTER_CLASS_SPECIALTY_DETAIL,
        {
          is_open: true,
          title: specialtyName,
          allow_clicking_outside: true,
          character_id: characterId,
          game_class_special_id: gameClassSpecialId,
        }
      );
    };

    return { openCharacterClassSpecialtyDetail };
  };
