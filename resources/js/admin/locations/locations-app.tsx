import React, { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';

import LocationsStandaloneApp from './components/locations-standalone-app';
import LocationsAppProps from './types/locations-app-props';

import AdminAppConfigurationError from '../shared/components/admin-app-configuration-error';
import { readPositiveDatasetInteger } from '../shared/utils/read-positive-dataset-integer';

import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';

const LocationsAdminApp = ({
  mount_element: mountElement,
}: LocationsAppProps): ReactNode => {
  const gameMapId = readPositiveDatasetInteger(mountElement, 'gameMapId');

  const renderContent = (): ReactNode => {
    if (gameMapId === null) {
      return (
        <AdminAppConfigurationError message="A valid Game Map is required to manage Locations." />
      );
    }

    return <LocationsStandaloneApp game_map_id={gameMapId} />;
  };

  return <ApiHandlerProvider>{renderContent()}</ApiHandlerProvider>;
};

const locationsElement = document.getElementById('locations-admin-app');

if (locationsElement) {
  createRoot(locationsElement).render(
    <LocationsAdminApp mount_element={locationsElement} />
  );
}
