import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import LocationGemBulkRollResultDefinition from '../api/definitions/location-gem-bulk-roll-result-definition';

export const useOpenLocationGemBulkRollResultSidePeek = (): ((
  result: LocationGemBulkRollResultDefinition
) => void) => {
  const emitter = useSidePeekEmitter();

  return (result: LocationGemBulkRollResultDefinition) =>
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_LOCATION_GEM_BULK_ROLL_RESULT,
      {
        is_open: true,
        title: 'Location Gem Roll All Results',
        allow_clicking_outside: true,
        result,
      }
    );
};
