import React, { ReactNode } from 'react';

import LocationGemFormDefinition from '../api/definitions/location-gem-form-definition';
import LocationGemFormContent from '../components/forms/location-gem-form-content';
import { LocationGemScreens } from '../screen-manager/location-gem-screen-constants';
import { useLocationGemScreenNavigation } from '../screen-manager/location-gem-screen-kit';
import { LocationGemFormScreenProps } from '../screen-manager/location-gem-screen-props';

const LocationGemFormScreen = ({
  location_gem_id: locationGemId,
}: LocationGemFormScreenProps): ReactNode => {
  const navigation = useLocationGemScreenNavigation();

  const handleSaved = (locationGem: LocationGemFormDefinition): void => {
    navigation.replaceWith(LocationGemScreens.SHOW, {
      location_gem_id: locationGem.id,
    });
  };

  const handleCancel = (): void => {
    navigation.pop();
  };

  return (
    <LocationGemFormContent
      location_gem_id={locationGemId}
      on_saved={handleSaved}
      on_cancel={handleCancel}
      embedded={false}
    />
  );
};

export default LocationGemFormScreen;
