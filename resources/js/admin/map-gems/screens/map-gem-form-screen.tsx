import React, { ReactNode } from 'react';

import MapGemFormDefinition from '../api/definitions/map-gem-form-definition';
import MapGemFormContent from '../components/forms/map-gem-form-content';
import { MapGemScreens } from '../screen-manager/map-gem-screen-constants';
import { useMapGemScreenNavigation } from '../screen-manager/map-gem-screen-kit';
import { MapGemFormScreenProps } from '../screen-manager/map-gem-screen-props';

const MapGemFormScreen = ({
  map_gem_id: mapGemId,
}: MapGemFormScreenProps): ReactNode => {
  const navigation = useMapGemScreenNavigation();

  const handleSaved = (mapGem: MapGemFormDefinition): void => {
    navigation.replaceWith(MapGemScreens.SHOW, { map_gem_id: mapGem.id });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <MapGemFormContent
      map_gem_id={mapGemId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default MapGemFormScreen;
