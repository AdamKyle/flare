import { AnimatePresence } from 'framer-motion';
import { useEventSystem } from 'event-system/hooks/use-event-system';
import React, { ReactNode, useEffect, useState } from 'react';

import GameMapEntitySelector from './components/game-map-entity-selector';
import GameMapMoveConfirmation from './components/game-map-move-confirmation';
import { GameMapSidePeekMessages } from './enums/game-map-side-peek-messages';
import { GameMapSidePeekView } from './enums/game-map-side-peek-view';
import GameMapCoordinateSidePeekProps from './types/game-map-coordinate-side-peek-props';
import { useManageGameMapMove } from '../../events/hooks/use-manage-game-map-move';
import { GameMapMoveEventMap } from '../../events/definitions/game-map-move-event-map';
import { GameMapMoveEvent } from '../../events/enums/game-map-move-event';
import { GameMapMarkerVariant } from '../../enums/game-map-marker-variant';
import { buildPlainCoordinate } from '../../utils/build-plain-coordinate';
import { findMarkersAtCoordinate } from '../../utils/find-markers-at-coordinate';
import { resolveMovingRecordTypeLabel } from '../../utils/resolve-moving-record-type-label';
import LocationFormScreen from '../../../locations/screens/location-form-screen';
import NpcFormScreen from '../../../npcs/screens/npc-form-screen';

import StackedCard from 'ui/cards/stacked-card';

const GameMapCoordinateSidePeek = ({
  game_map_id: gameMapId,
  x,
  y,
  locations,
  npcs,
  on_editor_changed: onEditorChanged,
  initial_move_state: initialMoveState,
}: GameMapCoordinateSidePeekProps): ReactNode => {
  const move = useManageGameMapMove(initialMoveState);
  const eventSystem = useEventSystem();
  const [view, setView] = useState<GameMapSidePeekView>(
    GameMapSidePeekView.Details
  );

  const [announcement, setAnnouncement] = useState('');

  useEffect(() => {
    const emitter = eventSystem.fetchOrCreateEventEmitter<GameMapMoveEventMap>(
      GameMapMoveEvent.STATE_CHANGED
    );
    const handleMoveSucceeded = (
      result: GameMapMoveEventMap[GameMapMoveEvent.SUCCEEDED]
    ): void => {
      setView(GameMapSidePeekView.Details);
      setAnnouncement(
        result.moving_record.kind === GameMapMarkerVariant.Npc
          ? GameMapSidePeekMessages.NpcMoved
          : GameMapSidePeekMessages.LocationMoved
      );
    };

    emitter.on(GameMapMoveEvent.SUCCEEDED, handleMoveSucceeded);

    return () => {
      emitter.off(GameMapMoveEvent.SUCCEEDED, handleMoveSucceeded);
    };
  }, [eventSystem]);

  const markersHere = findMarkersAtCoordinate(x, y, locations, npcs);
  const showingMoveConfirmation = Boolean(
    move.moving_record && move.pending_move_target
  );

  const handleCancelStacked = (): void => {
    setView(GameMapSidePeekView.Details);
  };

  const handleLocationSaved = async (): Promise<void> => {
    await onEditorChanged();
    setView(GameMapSidePeekView.Details);
    setAnnouncement(GameMapSidePeekMessages.LocationSaved);
  };

  const handleNpcSaved = async (): Promise<void> => {
    await onEditorChanged();
    setView(GameMapSidePeekView.Details);
    setAnnouncement(GameMapSidePeekMessages.NpcSaved);
  };

  const handleSelectLocationToMove = (locationId: number): void => {
    const location = locations.find((entry) => entry.id === locationId);

    move.start_move_location(
      locationId,
      location?.name ?? 'Location',
      location?.x ?? 0,
      location?.y ?? 0
    );
    move.select_move_target(buildPlainCoordinate(x, y));
    setView(GameMapSidePeekView.Details);
  };

  const handleSelectNpcToMove = (npcId: number): void => {
    const npc = npcs.find((entry) => entry.id === npcId);

    move.start_move_npc(
      npcId,
      npc?.real_name ?? 'Npc',
      npc?.x_position ?? 0,
      npc?.y_position ?? 0
    );
    move.select_move_target(buildPlainCoordinate(x, y));
    setView(GameMapSidePeekView.Details);
  };

  const handleConfirmMove = (): void => {
    move.confirm_move();
  };

  const renderEntitiesHere = (): ReactNode => {
    const total = markersHere.locations.length + markersHere.npcs.length;

    if (total === 0) {
      return (
        <p className="text-glacier-600 dark:text-glacier-300 text-sm">
          {GameMapSidePeekMessages.NoEntitiesAtCoordinate}
        </p>
      );
    }

    return (
      <ul className="space-y-2">
        {markersHere.locations.map((location) => (
          <li
            key={`location-${location.id}`}
            className="text-glacier-900 dark:text-glacier-100 text-sm"
          >
            Location: {location.name}
          </li>
        ))}
        {markersHere.npcs.map((npc) => (
          <li
            key={`npc-${npc.id}`}
            className="text-glacier-900 dark:text-glacier-100 text-sm"
          >
            Npc: {npc.real_name}
          </li>
        ))}
      </ul>
    );
  };

  const renderStackedContent = (): ReactNode => {
    if (view === GameMapSidePeekView.CreateLocation) {
      return (
        <StackedCard on_close={handleCancelStacked}>
          <LocationFormScreen
            game_map_id={gameMapId}
            location_id={null}
            initial_x={x}
            initial_y={y}
            on_saved={() => void handleLocationSaved()}
            on_cancel={handleCancelStacked}
            embedded
          />
        </StackedCard>
      );
    }

    if (view === GameMapSidePeekView.CreateNpc) {
      return (
        <StackedCard on_close={handleCancelStacked}>
          <NpcFormScreen
            game_map_id={gameMapId}
            npc_id={null}
            initial_x={x}
            initial_y={y}
            on_saved={() => void handleNpcSaved()}
            on_cancel={handleCancelStacked}
            embedded
          />
        </StackedCard>
      );
    }

    if (view === GameMapSidePeekView.SelectLocationToMove) {
      return (
        <StackedCard on_close={handleCancelStacked}>
          <GameMapEntitySelector
            entities={locations.map((location) => ({
              id: location.id,
              label: location.name,
              x: location.x,
              y: location.y,
            }))}
            search_label={GameMapSidePeekMessages.SearchLocations}
            empty_message={GameMapSidePeekMessages.NoLocationsToMove}
            on_select={handleSelectLocationToMove}
          />
        </StackedCard>
      );
    }

    if (view === GameMapSidePeekView.SelectNpcToMove) {
      return (
        <StackedCard on_close={handleCancelStacked}>
          <GameMapEntitySelector
            entities={npcs.map((npc) => ({
              id: npc.id,
              label: npc.real_name,
              x: npc.x_position,
              y: npc.y_position,
            }))}
            search_label={GameMapSidePeekMessages.SearchNpcs}
            empty_message={GameMapSidePeekMessages.NoNpcsToMove}
            on_select={handleSelectNpcToMove}
          />
        </StackedCard>
      );
    }

    return null;
  };

  const renderDefaultContent = (): ReactNode => (
    <div className="space-y-4 px-4">
      <p className="text-glacier-700 dark:text-glacier-300 text-sm">
        Selected coordinate X {x}, Y {y}.
      </p>

      {renderEntitiesHere()}

      <div className="flex flex-col gap-2">
        <button
          type="button"
          onClick={() => setView(GameMapSidePeekView.CreateLocation)}
          className="focus-visible:ring-danube-400 bg-danube-600 hover:bg-danube-500 rounded-md px-3 py-2 text-sm font-medium text-white focus:outline-none focus-visible:ring-2"
        >
          Create Location
        </button>
        <button
          type="button"
          onClick={() => setView(GameMapSidePeekView.CreateNpc)}
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border px-3 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Create NPC
        </button>
        <button
          type="button"
          onClick={() => setView(GameMapSidePeekView.SelectLocationToMove)}
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border px-3 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Move Location Here
        </button>
        <button
          type="button"
          onClick={() => setView(GameMapSidePeekView.SelectNpcToMove)}
          className="focus-visible:ring-glacier-400 border-glacier-300 text-glacier-700 hover:bg-glacier-50 dark:border-glacier-700 dark:text-glacier-200 dark:hover:bg-glacier-900 rounded-md border px-3 py-2 text-sm font-medium focus:outline-none focus-visible:ring-2"
        >
          Move NPC Here
        </button>
      </div>
    </div>
  );

  const renderAnnouncement = (): ReactNode => (
    <p className="sr-only" role="status" aria-live="polite">
      {announcement}
    </p>
  );

  if (
    showingMoveConfirmation &&
    move.moving_record &&
    move.pending_move_target
  ) {
    return (
      <div className="px-4">
        {renderAnnouncement()}
        <GameMapMoveConfirmation
          entity_type_label={resolveMovingRecordTypeLabel(move.moving_record)}
          entity_label={move.moving_record.label}
          origin_x={move.moving_record.origin_x}
          origin_y={move.moving_record.origin_y}
          destination_x={move.pending_move_target.x_value}
          destination_y={move.pending_move_target.y_value}
          is_moving={move.is_moving}
          api_error={move.move_error?.message ?? null}
          on_confirm={handleConfirmMove}
          on_cancel={move.cancel_move}
          on_clear_error={move.clear_move_error}
        />
      </div>
    );
  }

  return (
    <>
      {renderAnnouncement()}
      {renderDefaultContent()}
      <AnimatePresence mode="wait">{renderStackedContent()}</AnimatePresence>
    </>
  );
};

export default GameMapCoordinateSidePeek;
