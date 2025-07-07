import { isEmpty, isNil } from 'lodash';
import React, { useEffect, useState } from 'react';
import { match } from 'ts-pattern';

import { TeleportApiUrls } from './api/enums/teleport-api-urls';
import { useFetchTeleportCoordinatesApi } from './api/hooks/use-fetch-teleport-coordinates-api';
import { useTeleportPlayerApi } from './api/hooks/use-teleport-player-api';
import { CoordinateTypes } from './enums/coordinate-types';
import { LocationTypes } from './enums/location-types';
import CharacterKingdomsDropDown from './partials/character-kingdoms-drop-down';
import Coordinates from './partials/coordinates';
import EnemyKingdomsDropDown from './partials/enemy-kingdoms-drop-down';
import LocationsDropDown from './partials/locations-drop-down';
import NpcKingdomDropDown from './partials/npc-kingdoms-drop-down';
import PortLocationsDropDown from './partials/port-locations-drop-down';
import TeleportProps from './types/teleport-props';
import TeleportSection from '../../components/map-actions/teleport-section';
import { calculateCostOfTeleport } from '../util/calculate-cost-of-teleport';

import { GameDataError } from 'game-data/components/game-data-error';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';
import Separator from 'ui/separator/separator';

const Teleport = ({ character_data, x, y }: TeleportProps) => {
  const { data, error, loading } = useFetchTeleportCoordinatesApi({
    url: TeleportApiUrls.TELEPORT_COORDINATES,
    character_id: character_data.id,
  });

  const { setRequestParams } = useTeleportPlayerApi({
    url: TeleportApiUrls.TELEPORT_PLAYER,
    character_id: character_data.id,
  });

  const [selectedCoordinates, setSelectedCoordinates] = useState({
    x: x,
    y: y,
  });
  const [costOfTeleport, setCostOfTeleport] = useState(0);
  const [timeOutValue, setTimeOutvalue] = useState(0);
  const [canAffordToTeleport, setCanAffordToTeleport] = useState(true);
  const [selectedLocationType, setSelectedLocationType] =
    useState<LocationTypes | null>(null);

  useEffect(
    () => {
      if (isNil(selectedLocationType)) {
        return;
      }

      const calculatedCostOfTeleport = calculateCostOfTeleport({
        character_position: { x_position: x, y_position: y },
        new_character_position: {
          x_position: selectedCoordinates.x,
          y_position: selectedCoordinates.y,
        },
        character_gold: character_data.gold,
      });

      setCostOfTeleport(calculatedCostOfTeleport.cost);
      setTimeOutvalue(calculatedCostOfTeleport.time);
      setCanAffordToTeleport(calculatedCostOfTeleport.can_afford);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [selectedCoordinates]
  );

  const getObjectForCoordinates = (
    type: LocationTypes,
    id: number | string
  ) => {
    const value = match(type)
      .with(LocationTypes.PORT_LOCATION, () => {
        return data?.locations.find((location) => location.id === id);
      })
      .with(LocationTypes.LOCATION, () => {
        return data?.locations.find((location) => location.id === id);
      })
      .with(LocationTypes.MY_KINGDOM, () => {
        return data?.character_kingdoms.find(
          (myKingdom) => myKingdom.id === id
        );
      })
      .with(LocationTypes.NPC_KINGDOM, () => {
        return data?.npc_kingdoms.find((npcKingdom) => npcKingdom.id === id);
      })
      .with(LocationTypes.ENEMY_KINGDOM, () => {
        return data?.enemy_kingdoms.find(
          (enemyKingdom) => enemyKingdom.id === id
        );
      })
      .otherwise(() => {
        return null;
      });

    if (!value) {
      return null;
    }

    return value;
  };

  const handleSelectedItem = (
    selectedItem: DropdownItem,
    type: LocationTypes
  ) => {
    setSelectedLocationType(type);

    const objectForCoordinates = getObjectForCoordinates(
      type,
      selectedItem.value
    );

    if (objectForCoordinates === null) {
      return;
    }

    setSelectedCoordinates({
      x: objectForCoordinates.x_position,
      y: objectForCoordinates.y_position,
    });
  };

  const handleOnClearDropDown = () => {
    setSelectedLocationType(null);
    setSelectedCoordinates({
      x,
      y,
    });
    setCostOfTeleport(0);
    setCanAffordToTeleport(false);
    setTimeOutvalue(0);
  };

  const handleClearCoordinateType = (coordinateType: CoordinateTypes) => {
    setSelectedLocationType(null);
    setSelectedCoordinates((prev) => ({
      x: coordinateType === CoordinateTypes.X ? x : prev.x,
      y: coordinateType === CoordinateTypes.Y ? y : prev.y,
    }));
    setCostOfTeleport(0);
    setCanAffordToTeleport(false);
    setTimeOutvalue(0);
  };

  const handleUpdatingCoordinateSelection = (coordinates: {
    x: number;
    y: number;
  }) => {
    setSelectedCoordinates(coordinates);
    setSelectedLocationType(LocationTypes.COORDINATE);
  };

  const handleTeleportPlayer = () => {
    setRequestParams({
      x: selectedCoordinates.x,
      y: selectedCoordinates.y,
      cost: costOfTeleport,
      timeout: timeOutValue,
    });
  };

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
          on_select={handleSelectedItem}
          on_clear={handleOnClearDropDown}
          location_type_selected={selectedLocationType}
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
        <EnemyKingdomsDropDown
          enemy_kingdoms={data.enemy_kingdoms}
          on_select={handleSelectedItem}
          on_clear={handleOnClearDropDown}
          location_type_selected={selectedLocationType}
        />
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
        <NpcKingdomDropDown
          npc_kingdoms={data.npc_kingdoms}
          on_select={handleSelectedItem}
          on_clear={handleOnClearDropDown}
          location_type_selected={selectedLocationType}
        />
      </div>
    );
  };

  const renderCostSection = () => {
    return (
      <TeleportSection
        character_gold={character_data.gold}
        cost_of_teleport={costOfTeleport}
        on_teleport={handleTeleportPlayer}
        can_afford_to_teleport={canAffordToTeleport}
        time_out_value={timeOutValue}
      />
    );
  };

  return (
    <div className="p-4 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
      <div className="grid gap-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Locations
        </label>
        <LocationsDropDown
          locations={data.locations.filter((location) => !location.is_port)}
          on_select={handleSelectedItem}
          on_clear={handleOnClearDropDown}
          location_type_selected={selectedLocationType}
        />
      </div>
      <div className="grid gap-2 my-2">
        <label className="text-sm font-semibold text-gray-700 dark:text-gray-300">
          Ports
        </label>
        <PortLocationsDropDown
          locations={data.locations.filter((location) => location.is_port)}
          location_type_selected={selectedLocationType}
          on_clear={handleOnClearDropDown}
          on_select={handleSelectedItem}
        />
      </div>

      <Separator />

      <div className="space-y-4">
        {renderCharacterKingdoms()}
        {renderEnemyKingdoms()}
        {renderNpcKingdoms()}
      </div>

      <Separator />

      <Coordinates
        coordinates={data.coordinates}
        x={selectedCoordinates.x}
        y={selectedCoordinates.y}
        on_select_coordinates={handleUpdatingCoordinateSelection}
        on_clear_coordinates={handleClearCoordinateType}
      />

      <Separator />
      {renderCostSection()}
    </div>
  );
};

export default Teleport;
