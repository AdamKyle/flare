import UseCharacterGemBagDefinition from './definition/use-character-gem-bag-definition';
import UseOpenCharacterGemBagProps from './types/use-open-character-gem-bag-props';
import { SidePeekComponentRegistrationEnum } from '../../../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenCharacterGemBag = (
  props: UseOpenCharacterGemBagProps
): UseCharacterGemBagDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openGemBag = () => {
    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.GEM_BAG,
      {
        is_open: true,
        title: 'Gem Bag',
        character_id: props.character_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openGemBag,
  };
};
