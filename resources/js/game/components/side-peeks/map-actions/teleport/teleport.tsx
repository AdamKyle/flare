import { isEmpty, isNil } from 'lodash';
import React, { useState } from 'react';

import { TeleportModalUrls } from './api/enums/teleport-modal-urls';
import { useFetchTeleportCoordinatesApi } from './api/hooks/use-fetch-teleport-coordinates-api';
import CharacterKingdomsDropDown from './partials/character-kingdoms-drop-down';
import CoordinatesDropDown from './partials/coordinates-drop-down';
import EnemyKingdomsDropDown from './partials/enemy-kingdoms-drop-down';
import LocationsDropDown from './partials/locations-drop-down';
import NpcKingdomDropDown from './partials/npc-kingdoms-drop-down';
import TeleportProps from './types/teleport-props';

import { GameDataError } from 'game-data/components/game-data-error';

import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/seperatror/separator';

const Teleport = ({ character_id }: TeleportProps) => {
  const { data, error, loading } = useFetchTeleportCoordinatesApi({
    url: TeleportModalUrls.TELEPORT_COORDINATES,
    character_id,
  });

  const [hasSelectedLocation, setHasSelectedLocation] = useState(false);
  const [selectedCoordinates, setSelectedCoordinates] = useState({
    x: 0,
    y: 0,
  });
  const [costOfTeleport, setCostOfTeleport] = useState(0);

  if (loading) {
    return (
      <div className="flex items-center justify-center p-4 bg-white dark:bg-gray-800">
        <InfiniteLoader />
      </div>
    );
  }

  if (error || isNil(data)) {
    return (
      <div className="flex items-center justify-center p-4 bg-white dark:bg-gray-800">
        <GameDataError />
      </div>
    );
  }

  const renderCharacterKingdoms = () => {
    if (isEmpty(data.character_kingdoms)) {
      return null;
    }

    return (
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Your Kingdoms
        </label>
        <CharacterKingdomsDropDown
          character_kingdoms={data.character_kingdoms}
        />
      </div>
    );
  };

  const renderEnemyKingdoms = () => {
    if (isEmpty(data.enemy_kingdoms)) {
      return null;
    }

    return (
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Enemy Kingdoms
        </label>
        <EnemyKingdomsDropDown enemy_kingdoms={data.enemy_kingdoms} />
      </div>
    );
  };

  const renderNpcKingdoms = () => {
    if (isEmpty(data.npc_kingdoms)) {
      return null;
    }

    return (
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          NPC Kingdoms
        </label>
        <NpcKingdomDropDown npc_kingdoms={data.npc_kingdoms} />
      </div>
    );
  };

  return (
    <div className="p-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Location
        </label>
        <LocationsDropDown locations={data.locations} />
      </div>

      <Separator />

      <div className="space-y-4">
        {renderCharacterKingdoms()}
        {renderEnemyKingdoms()}
        {renderNpcKingdoms()}
      </div>

      <Separator />

      <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div className="grid gap-2">
          <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
            X Coordinate
          </label>
          <CoordinatesDropDown coordinates={data.coordinates.x} />
        </div>
        <div className="grid gap-2">
          <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
            Y Coordinate
          </label>
          <CoordinatesDropDown coordinates={data.coordinates.y} />
        </div>
      </div>
    </div>
  );
};

export default Teleport;
