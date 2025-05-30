import UseOpenCharacterKingdomInfoModalDefinition from './definitions/use-open-location-info-modal-definition';
import UseOpenLocationInfoSidePeekProps from './types/use-open-location-info-side-peek-props';
import { SidePeekComponentRegistrationEnum } from '../../side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../side-peeks/base/hooks/use-side-peek-emitter';

export const useOpenLocationInfoSidePeek = (
  props: UseOpenLocationInfoSidePeekProps
): UseOpenCharacterKingdomInfoModalDefinition => {
  const sidePeekEmitter = useSidePeekEmitter();

  const openLocationDetails = (location_id: number, location_name: string) => {
    if (!props.characterData) {
      return;
    }

    sidePeekEmitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.LOCATION_DETAILS,
      {
        is_open: true,
        title: location_name,
        character_id: props.characterData.id,
        location_id: location_id,
        allow_clicking_outside: true,
      }
    );
  };

  return {
    openLocationDetails,
  };
};
