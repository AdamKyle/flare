import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import MapGemBulkRollResultDefinition from '../api/definitions/map-gem-bulk-roll-result-definition';

export const useOpenMapGemBulkRollResultSidePeek = (): ((
  result: MapGemBulkRollResultDefinition
) => void) => {
  const emitter = useSidePeekEmitter();

  return (result: MapGemBulkRollResultDefinition) =>
    emitter.emit(
      SidePeek.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_MAP_GEM_BULK_ROLL_RESULT,
      {
        is_open: true,
        title: 'Map Gem Roll All Results',
        allow_clicking_outside: true,
        result,
      }
    );
};
