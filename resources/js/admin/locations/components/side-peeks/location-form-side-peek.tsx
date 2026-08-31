import React, { ReactNode } from 'react';

import LocationFormSidePeekProps from './types/location-form-side-peek-props';
import { useCloseSidePeekEmitter } from '../../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';
import LocationFormScreen from '../../screens/location-form-screen';

const LocationFormSidePeek = ({
  game_map_id: gameMapId,
  location_id: locationId,
  on_saved: onSaved,
}: LocationFormSidePeekProps): ReactNode => {
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const handleSaved: LocationFormSidePeekProps['on_saved'] = (location) => {
    onSaved(location);
    closeSidePeek();
  };

  return (
    <LocationFormScreen
      game_map_id={gameMapId}
      location_id={locationId}
      initial_x={null}
      initial_y={null}
      on_saved={handleSaved}
      on_cancel={closeSidePeek}
      embedded
    />
  );
};

export default LocationFormSidePeek;
