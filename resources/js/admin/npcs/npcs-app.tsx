import React, { ReactNode } from 'react';
import { createRoot } from 'react-dom/client';

import NpcsStandaloneApp from './components/npcs-standalone-app';
import NpcsAppProps from './types/npcs-app-props';

import AdminAppConfigurationError from '../shared/components/admin-app-configuration-error';
import { readPositiveDatasetInteger } from '../shared/utils/read-positive-dataset-integer';

import { ApiHandlerProvider } from 'api-handler/components/api-handler-provider';

const NpcsAdminApp = ({
  mount_element: mountElement,
}: NpcsAppProps): ReactNode => {
  const gameMapId = readPositiveDatasetInteger(mountElement, 'gameMapId');

  const renderContent = (): ReactNode => {
    if (gameMapId === null) {
      return (
        <AdminAppConfigurationError message="A valid Game Map is required to manage Npcs." />
      );
    }

    return <NpcsStandaloneApp game_map_id={gameMapId} />;
  };

  return <ApiHandlerProvider>{renderContent()}</ApiHandlerProvider>;
};

const npcsElement = document.getElementById('npcs-admin-app');

if (npcsElement) {
  createRoot(npcsElement).render(<NpcsAdminApp mount_element={npcsElement} />);
}
