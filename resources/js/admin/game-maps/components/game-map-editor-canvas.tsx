import React, {
  PointerEvent as ReactPointerEvent,
  ReactNode,
  useId,
  useRef,
} from 'react';

import GameMapCoordinateGridLayer from './game-map-coordinate-grid-layer';
import GameMapMarkerLayer from './game-map-marker-layer';
import GameMapSelectedCellLayer from './game-map-selected-cell-layer';
import GameMapTileLayer from './game-map-tile-layer';
import { useGameMapCanvasFocus } from '../hooks/use-game-map-canvas-focus';
import { useGameMapCanvasReset } from '../hooks/use-game-map-canvas-reset';
import { useGameMapCoordinateHighlight } from '../hooks/use-game-map-coordinate-highlight';
import { useGameMapEditorPan } from '../hooks/use-game-map-editor-pan';
import CoordinateDefinition from '../types/coordinate-definition';
import GameMapEditorCanvasProps from '../types/game-map-editor-canvas-props';
import { resolveGameMapKeyboardNavigation } from '../utils/resolve-game-map-keyboard-navigation';
import { resolvePointerCoordinate } from '../utils/resolve-pointer-coordinate';
import { selectableCoordinateValues } from '../utils/selectable-coordinates';

import { MapTileSize } from 'game-utils/map-tile-size';

const GameMapEditorCanvas = ({
  editor,
  selected_coordinate,
  on_select_coordinate,
  moving_record,
  on_move_target_selected,
  on_cancel_active_mode,
  reset_token,
  focus_token,
  on_location_selected,
  on_npc_selected,
  on_kingdom_selected,
}: GameMapEditorCanvasProps): ReactNode => {
  const instructionsId = useId();
  const containerRef = useRef<HTMLDivElement>(null);
  const gridRef = useRef<HTMLDivElement>(null);

  const tiles = editor.game_map.tiles;
  const mapWidth = (tiles[0]?.length ?? 0) * MapTileSize.TILE_SIZE;
  const mapHeight = tiles.length * MapTileSize.TILE_SIZE;
  const hasTiles = tiles.length > 0 && mapWidth > 0 && mapHeight > 0;
  const xValues = selectableCoordinateValues(editor.coordinates.x, mapWidth);
  const yValues = selectableCoordinateValues(editor.coordinates.y, mapHeight);

  const pan = useGameMapEditorPan(containerRef, mapWidth, mapHeight);
  const { highlighted, set_highlighted: setHighlighted } =
    useGameMapCoordinateHighlight(selected_coordinate);

  useGameMapCanvasReset(reset_token, pan.reset_translate);
  useGameMapCanvasFocus(focus_token, gridRef);

  const activateCoordinate = (coordinate: CoordinateDefinition): void => {
    setHighlighted(coordinate);

    if (moving_record) {
      on_move_target_selected(coordinate);

      return;
    }

    on_select_coordinate({ coordinate });
  };

  const { handle_key_down: handleKeyDown } = resolveGameMapKeyboardNavigation(
    xValues,
    yValues,
    highlighted,
    setHighlighted,
    pan.pan_into_view,
    activateCoordinate,
    on_cancel_active_mode
  );

  const handlePointerUp = (event: ReactPointerEvent<HTMLDivElement>): void => {
    const wasPanning = pan.end_drag(event);

    if (wasPanning) {
      return;
    }

    const container = containerRef.current;

    if (!container) {
      return;
    }

    const rect = container.getBoundingClientRect();
    const localX = event.clientX - rect.left - pan.translate.x;
    const localY = event.clientY - rect.top - pan.translate.y;
    const coordinate = resolvePointerCoordinate(
      localX,
      localY,
      xValues,
      yValues,
      mapWidth,
      mapHeight
    );

    if (!coordinate) {
      return;
    }

    activateCoordinate(coordinate);
  };

  const accessibleName = moving_record
    ? `${editor.game_map.name} map grid. Select a target coordinate to move ${moving_record.label}.`
    : `${editor.game_map.name} map grid`;

  if (!hasTiles) {
    return (
      <p
        role="status"
        className="rounded-md border border-gray-200 p-4 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-300"
      >
        No tile map is available for this Game Map. The coordinate editor is
        unavailable until this Game Map has tiles.
      </p>
    );
  }

  return (
    <div
      ref={containerRef}
      className="relative h-full w-full touch-none overflow-hidden rounded-md border border-gray-300 bg-gray-900 dark:border-gray-700"
    >
      <div
        ref={gridRef}
        tabIndex={0}
        aria-label={accessibleName}
        aria-describedby={instructionsId}
        onPointerDown={pan.handle_pointer_down}
        onPointerMove={pan.handle_pointer_move}
        onPointerUp={handlePointerUp}
        onPointerCancel={pan.handle_pointer_cancel}
        onLostPointerCapture={pan.handle_lost_pointer_capture}
        onKeyDown={handleKeyDown}
        className="focus-visible:ring-mango-tango-400 absolute top-0 left-0 focus:outline-none focus-visible:ring-2"
        style={{
          width: mapWidth,
          height: mapHeight,
          transform: `translate(${pan.translate.x}px, ${pan.translate.y}px)`,
        }}
      >
        <span className="sr-only" aria-live="polite">
          {highlighted
            ? `Highlighted coordinate X ${highlighted.x_value}, Y ${highlighted.y_value}`
            : ''}
        </span>
        <GameMapTileLayer tiles={tiles} />
        <GameMapCoordinateGridLayer
          x_values={xValues}
          y_values={yValues}
          map_width={mapWidth}
          map_height={mapHeight}
        />
        <GameMapSelectedCellLayer highlighted={highlighted} />
        <GameMapMarkerLayer
          locations={editor.locations}
          npcs={editor.npcs}
          kingdoms={editor.kingdoms}
          on_location_selected={on_location_selected}
          on_npc_selected={on_npc_selected}
          on_kingdom_selected={on_kingdom_selected}
        />
      </div>
      <p id={instructionsId} className="sr-only">
        Use Arrow keys to move one coordinate square. Home moves to the first X
        coordinate in the current row. End moves to the final valid X coordinate
        in the current row. Page Up and Page Down move ten coordinate rows.
        Press Enter to select the highlighted coordinate. Press Escape to cancel
        the current selection or move.
      </p>
    </div>
  );
};

export default GameMapEditorCanvas;
