import ApiErrorAlert from 'api-handler/components/api-error-alert';
import { isEmpty, isNil } from 'lodash';
import React, { useState } from 'react';

import SetSailPortDefinition from './api/definitions/set-sail-port-definition';
import { useFetchSetSailPortsApi } from './api/hooks/use-fetch-set-sail-ports-api';
import { useSetSailApi } from './api/hooks/use-set-sail-api';
import PortLocationsDropDown from './partials/port-locations-drop-down';
import SetSailSection from './partials/set-sail-section';
import SetSailProps from './types/set-sail-props';

import { GameDataError } from 'game-data/components/game-data-error';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const DESTINATION_PORT_LABEL_ID = 'set-sail-destination-port-label';

const SetSail = ({ character_data }: SetSailProps) => {
  const { data, loading, error } = useFetchSetSailPortsApi({
    character_id: character_data.id,
  });

  const {
    setSail,
    loading: isSettingSail,
    error: setSailError,
  } = useSetSailApi({
    character_id: character_data.id,
  });

  const [selectedPort, setSelectedPort] =
    useState<SetSailPortDefinition | null>(null);

  if (loading) {
    return (
      <div
        className="flex items-center justify-center bg-white p-4 dark:bg-gray-800"
        role="status"
        aria-live="polite"
      >
        <InfiniteLoader />
        <span className="sr-only">Loading Set Sail ports.</span>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-white p-4 dark:bg-gray-800">
        <ApiErrorAlert apiError={error.message} />
      </div>
    );
  }

  if (isNil(data)) {
    return (
      <div className="flex items-center justify-center bg-white p-4 dark:bg-gray-800">
        <GameDataError />
      </div>
    );
  }

  const handleSelectPort = (port: SetSailPortDefinition) => {
    setSelectedPort(port);
  };

  const handleClearPort = () => {
    setSelectedPort(null);
  };

  const handleSetSail = async () => {
    if (!selectedPort) {
      return;
    }

    await setSail({
      x: selectedPort.x,
      y: selectedPort.y,
      cost: selectedPort.cost,
      timeout: selectedPort.time,
    });
  };

  const renderEmptyPorts = () => {
    if (!isEmpty(data.port_list)) {
      return null;
    }

    return (
      <p className="text-sm text-gray-700 dark:text-gray-300">
        There are no other ports available on this map.
      </p>
    );
  };

  const renderDestinationDropdown = () => {
    if (isEmpty(data.port_list)) {
      return null;
    }

    return (
      <div className="grid gap-2">
        <label
          id={DESTINATION_PORT_LABEL_ID}
          className="text-sm font-semibold text-gray-700 dark:text-gray-300"
        >
          Destination Port
        </label>
        <PortLocationsDropDown
          ports={data.port_list}
          on_select={handleSelectPort}
          on_clear={handleClearPort}
          aria_labelled_by={DESTINATION_PORT_LABEL_ID}
        />
      </div>
    );
  };

  const renderSelectedPortDetails = () => {
    if (!selectedPort) {
      return null;
    }

    return (
      <SetSailSection
        character_gold={character_data.gold}
        selected_port={selectedPort}
        on_set_sail={handleSetSail}
        is_submitting={isSettingSail}
      />
    );
  };

  const renderSetSailError = () => {
    if (!setSailError) {
      return null;
    }

    return <ApiErrorAlert apiError={setSailError.message} />;
  };

  return (
    <div className="bg-white p-4 text-gray-900 dark:bg-gray-800 dark:text-gray-100">
      <div className="text-sm text-gray-700 dark:text-gray-300">
        <span className="font-semibold">Current Port:</span>{' '}
        {data.current_port.name} ({data.current_port.x} / {data.current_port.y})
      </div>

      <Separator />

      {renderEmptyPorts()}
      {renderDestinationDropdown()}

      {renderSetSailError()}
      {renderSelectedPortDetails()}
    </div>
  );
};

export default SetSail;
