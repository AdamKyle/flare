import ApiErrorAlert from 'api-handler/components/api-error-alert';
import React, { ReactNode, useCallback, useState } from 'react';

import { SidePeekComponentRegistrationEnum } from '../../../game/components/side-peeks/base/component-registration/side-peek-component-registration-enum';
import { SidePeek as SidePeekEventType } from '../../../game/components/side-peeks/base/event-types/side-peek';
import { useCloseSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-close-side-peek-emitter';
import { useSidePeekEmitter } from '../../../game/components/side-peeks/base/hooks/use-side-peek-emitter';
import AdminBackButton from '../../shared/components/admin-back-button';
import AdminPage from '../../shared/components/admin-page';
import { AdminPageWidth } from '../../shared/enums/admin-page-width';
import GameMapKingdomMarkerDefinition from '../api/definitions/game-map-kingdom-marker-definition';
import { GameMapApiMessages } from '../api/enums/game-map-api-messages';
import { useGameMapEditor } from '../api/hooks/use-game-map-editor';
import GameMapEditorCanvas from '../components/game-map-editor-canvas';
import GameMapEditorLegend from '../components/game-map-editor-legend';
import GameMapEditorToolbar from '../components/game-map-editor-toolbar';
import { GameMapMoveStateDefinition } from '../events/definitions/game-map-move-event-map';
import { useOwnGameMapMove } from '../events/hooks/use-own-game-map-move';
import { useGameMapScreenNavigation } from '../screen-manager/game-map-screen-kit';
import CoordinateDefinition from '../types/coordinate-definition';
import GameMapEditorScreenProps from '../types/game-map-editor-screen-props';
import SelectedCoordinateDefinition from '../types/selected-coordinate-definition';
import { resolveMovingRecordTypeLabel } from '../utils/resolve-moving-record-type-label';

import { Alert } from 'ui/alerts/alert';
import { AlertVariant } from 'ui/alerts/enums/alert-variant';
import InfiniteLoader from 'ui/loading-bar/infinite-loader';

const GameMapEditorScreen = ({
  game_map_id: gameMapId,
}: GameMapEditorScreenProps): ReactNode => {
  const navigation = useGameMapScreenNavigation();
  const { editor, loading, error, refresh } = useGameMapEditor(gameMapId);
  const handleMoveSucceeded = useCallback(async (): Promise<void> => {
    await refresh();
  }, [refresh]);
  const move = useOwnGameMapMove({
    game_map_id: gameMapId,
    on_move_succeeded: handleMoveSucceeded,
  });
  const sidePeekEmitter = useSidePeekEmitter();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const [selectedCoordinate, setSelectedCoordinate] =
    useState<SelectedCoordinateDefinition | null>(null);
  const [resetToken, setResetToken] = useState(0);
  const [focusToken, setFocusToken] = useState(0);
  const [announcement, setAnnouncement] = useState('');

  const handleEditorChanged = async (): Promise<void> => {
    refresh();
  };

  const handleBack = (): void => {
    navigation.pop();
  };

  const requestFocusReturn = (): void => {
    setFocusToken((value) => value + 1);
  };

  const handleSidePeekClosed = (): void => {
    requestFocusReturn();
  };

  const openCoordinateSidePeek = (
    coordinate: CoordinateDefinition,
    initialMoveState?: GameMapMoveStateDefinition
  ): void => {
    if (!editor) {
      return;
    }

    const resolvedInitialMoveState = initialMoveState ?? {
      moving_record: move.moving_record,
      pending_move_target: move.pending_move_target,
      is_moving: move.is_moving,
      move_error: move.move_error,
    };

    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_COORDINATE,
      {
        is_open: true,
        title: `Coordinate X ${coordinate.x_value}, Y ${coordinate.y_value}`,
        allow_clicking_outside: true,
        on_close: handleSidePeekClosed,
        game_map_id: gameMapId,
        x: coordinate.x_value,
        y: coordinate.y_value,
        locations: editor.locations,
        npcs: editor.npcs,
        on_editor_changed: handleEditorChanged,
        initial_move_state: resolvedInitialMoveState,
      }
    );
  };

  const openLocationSidePeek = (locationId: number): void => {
    const locationMarker = editor?.locations.find(
      (entry) => entry.id === locationId
    );

    if (!locationMarker) {
      setAnnouncement(GameMapApiMessages.LoadLocationMarker);

      return;
    }

    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_LOCATION,
      {
        is_open: true,
        title: 'Location Details',
        allow_clicking_outside: true,
        on_close: handleSidePeekClosed,
        game_map_id: gameMapId,
        location_id: locationId,
        on_editor_changed: handleEditorChanged,
        on_move_requested: handleMoveLocationRequested,
      }
    );
  };

  const openNpcSidePeek = (npcId: number): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_NPC,
      {
        is_open: true,
        title: 'Npc Details',
        allow_clicking_outside: true,
        on_close: handleSidePeekClosed,
        game_map_id: gameMapId,
        npc_id: npcId,
        on_editor_changed: handleEditorChanged,
        on_move_requested: handleMoveNpcRequested,
      }
    );
  };

  const openKingdomSidePeek = (
    kingdom: GameMapKingdomMarkerDefinition
  ): void => {
    sidePeekEmitter.emit(
      SidePeekEventType.SIDE_PEEK,
      SidePeekComponentRegistrationEnum.ADMIN_GAME_MAP_KINGDOM,
      {
        is_open: true,
        title: `Kingdom: ${kingdom.name}`,
        allow_clicking_outside: true,
        on_close: handleSidePeekClosed,
        kingdom,
      }
    );
  };

  const handleSelectCoordinate = (
    selected: SelectedCoordinateDefinition | null
  ): void => {
    setSelectedCoordinate(selected);

    if (!selected) {
      return;
    }

    openCoordinateSidePeek(selected.coordinate);
  };

  const handleMoveTargetSelected = (coordinate: CoordinateDefinition): void => {
    move.select_move_target(coordinate);
    const initialMoveState: GameMapMoveStateDefinition = {
      moving_record: move.moving_record,
      pending_move_target: coordinate,
      is_moving: move.is_moving,
      move_error: move.move_error,
    };

    openCoordinateSidePeek(coordinate, initialMoveState);
  };

  const handleMoveLocationRequested = (locationId: number): void => {
    const location = editor?.locations.find((entry) => entry.id === locationId);

    closeSidePeek();
    setSelectedCoordinate(null);
    move.start_move_location(
      locationId,
      location?.name ?? 'Location',
      location?.x ?? 0,
      location?.y ?? 0
    );
    setAnnouncement(
      `Select a destination coordinate to move ${location?.name ?? 'this Location'}.`
    );
  };

  const handleMoveNpcRequested = (npcId: number): void => {
    const npc = editor?.npcs.find((entry) => entry.id === npcId);

    closeSidePeek();
    setSelectedCoordinate(null);
    move.start_move_npc(
      npcId,
      npc?.real_name ?? 'Npc',
      npc?.x_position ?? 0,
      npc?.y_position ?? 0
    );
    setAnnouncement(
      `Select a destination coordinate to move ${npc?.real_name ?? 'this Npc'}.`
    );
  };

  const handleCancelActiveMode = (): void => {
    if (move.moving_record) {
      move.cancel_move();

      return;
    }

    if (selectedCoordinate) {
      setSelectedCoordinate(null);
    }
  };

  const handleCancelMove = (): void => {
    move.cancel_move();
    requestFocusReturn();
  };

  const handleResetView = (): void => {
    setResetToken((value) => value + 1);
  };

  const selectedLabel = selectedCoordinate
    ? `X ${selectedCoordinate.coordinate.x_value}, Y ${selectedCoordinate.coordinate.y_value}`
    : null;

  const renderMovingBanner = (): ReactNode => {
    if (!move.moving_record) {
      return null;
    }

    return (
      <div
        role="status"
        className="border-mango-tango-400 bg-mango-tango-50 mb-4 flex flex-wrap items-center justify-between gap-3 rounded-md border p-3 dark:bg-gray-900"
      >
        <p className="text-sm text-gray-800 dark:text-gray-200">
          Moving {resolveMovingRecordTypeLabel(move.moving_record)}:{' '}
          {move.moving_record.label}. Select a destination coordinate on the
          map.
        </p>
        <button
          type="button"
          onClick={handleCancelMove}
          className="text-danube-600 focus:ring-danube-500 dark:text-danube-300 text-sm font-medium hover:underline focus:ring-2 focus:outline-none"
        >
          Cancel Move
        </button>
      </div>
    );
  };

  if (loading) {
    return <InfiniteLoader />;
  }

  if (error || !editor) {
    return (
      <ApiErrorAlert
        apiError={error?.message ?? GameMapApiMessages.LoadEditor}
      />
    );
  }

  if (editor.game_map.tiles.length === 0) {
    return (
      <AdminPage
        title={`Edit Locations for: ${editor.game_map.name}`}
        width={AdminPageWidth.Workspace}
        header_actions={<AdminBackButton on_click={handleBack} />}
      >
        <Alert variant={AlertVariant.INFO}>
          Map tile processing is not complete. Refresh the Game Map detail page
          after processing finishes before opening the coordinate editor.
        </Alert>
      </AdminPage>
    );
  }

  return (
    <AdminPage
      title={`Edit Locations for: ${editor.game_map.name}`}
      width={AdminPageWidth.Workspace}
      header_actions={<AdminBackButton on_click={handleBack} />}
    >
      <div className="flex min-h-0 flex-1 flex-col">
        <div className="mb-4 flex-none">
          <GameMapEditorLegend />
        </div>

        <p className="sr-only" role="status" aria-live="polite">
          {announcement}
        </p>

        <div className="flex-none">{renderMovingBanner()}</div>

        {move.move_error && (
          <div className="flex-none">
            <ApiErrorAlert
              apiError={move.move_error.message}
              closable
              on_close={move.clear_move_error}
            />
          </div>
        )}

        <div className="flex-none">
          <GameMapEditorToolbar
            selected_label={selectedLabel}
            on_reset_view={handleResetView}
          />
        </div>

        <div className="min-h-0 flex-1 overflow-hidden">
          <GameMapEditorCanvas
            editor={editor}
            selected_coordinate={selectedCoordinate}
            on_select_coordinate={handleSelectCoordinate}
            moving_record={move.moving_record}
            on_move_target_selected={handleMoveTargetSelected}
            on_cancel_active_mode={handleCancelActiveMode}
            reset_token={resetToken}
            focus_token={focusToken}
            on_location_selected={openLocationSidePeek}
            on_npc_selected={openNpcSidePeek}
            on_kingdom_selected={openKingdomSidePeek}
          />
        </div>
      </div>
    </AdminPage>
  );
};

export default GameMapEditorScreen;
