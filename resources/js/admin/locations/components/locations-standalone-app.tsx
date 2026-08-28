import React, { ReactNode, useState } from 'react';

import LocationDefinition from '../api/definitions/location-definition';
import LocationFormScreen from '../screens/location-form-screen';
import LocationsStandaloneAppProps from '../types/locations-standalone-app-props';

import Button from 'ui/buttons/button';
import { ButtonVariant } from 'ui/buttons/enums/button-variant-enum';

/**
 * Standalone embedded mount for the Location form, used when this entry is mounted on
 * its own outside the Game Maps editor stack. Owns only the state needed to show a
 * saved-success confirmation and to reset the form back to its initial state.
 */
const LocationsStandaloneApp = ({
  game_map_id,
}: LocationsStandaloneAppProps): ReactNode => {
  const [formInstanceKey, setFormInstanceKey] = useState(0);
  const [savedLocation, setSavedLocation] = useState<LocationDefinition | null>(
    null
  );

  const handleSaved = (location: LocationDefinition): void => {
    setSavedLocation(location);
  };

  const handleReset = (): void => {
    setSavedLocation(null);
    setFormInstanceKey((value) => value + 1);
  };

  if (savedLocation) {
    return (
      <div className="container mx-auto my-4 px-4">
        <p
          role="status"
          className="mb-4 text-sm text-gray-800 dark:text-gray-200"
        >
          Location &quot;{savedLocation.name}&quot; saved.
        </p>
        <Button
          label="Add Another Location"
          variant={ButtonVariant.PRIMARY}
          on_click={handleReset}
        />
      </div>
    );
  }

  return (
    <LocationFormScreen
      key={formInstanceKey}
      game_map_id={game_map_id}
      location_id={null}
      initial_x={null}
      initial_y={null}
      on_saved={handleSaved}
      on_cancel={handleReset}
    />
  );
};

export default LocationsStandaloneApp;
