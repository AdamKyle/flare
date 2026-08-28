import React, { ReactNode } from 'react';

import GameMapEditorLegendEntry from '../types/game-map-editor-legend-entry';
import { GAME_MAP_EDITOR_LEGEND_ENTRIES } from '../values/game-map-editor-legend-entries';
import {
  GAME_MAP_MARKER_COLOR,
  GAME_MAP_MARKER_ICON,
} from '../values/game-map-marker-variants';

const GameMapEditorLegend = (): ReactNode => {
  const renderLegendEntry = (entry: GameMapEditorLegendEntry): ReactNode => (
    <li key={entry.label} className="flex items-center gap-2">
      <i
        className={`${GAME_MAP_MARKER_ICON[entry.variant]} ${GAME_MAP_MARKER_COLOR[entry.variant]}`}
        aria-hidden="true"
      />
      <span>{entry.label}</span>
    </li>
  );

  return (
    <ul
      aria-label="Map legend"
      className="flex flex-wrap gap-4 text-sm text-gray-700 dark:text-gray-300"
    >
      {GAME_MAP_EDITOR_LEGEND_ENTRIES.map(renderLegendEntry)}
    </ul>
  );
};

export default GameMapEditorLegend;
