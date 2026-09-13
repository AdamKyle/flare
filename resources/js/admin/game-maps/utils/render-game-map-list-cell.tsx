import clsx from 'clsx';
import React, { ReactNode } from 'react';

import GameMapDefinition from '../api/definitions/game-map-definition';
import { GameMapType } from '../enums/game-map-type';

const GAME_MAP_TYPE_ICONS: Record<GameMapType, string | null> = {
  [GameMapType.BASE]: null,
  [GameMapType.MAP_GEM_WORLD]: 'fas fa-gem',
  [GameMapType.LOCATION_GEM_WORLD]: 'fas fa-map-marker-alt',
};

const GAME_MAP_TYPE_TEXT_STYLES: Record<GameMapType, string> = {
  [GameMapType.BASE]: 'text-glacier-900 dark:text-glacier-100',
  [GameMapType.MAP_GEM_WORLD]: 'text-indigo-700 dark:text-indigo-300',
  [GameMapType.LOCATION_GEM_WORLD]: 'text-emerald-700 dark:text-emerald-300',
};

export const renderGameMapListCell = (row: GameMapDefinition): ReactNode => {
  const icon = GAME_MAP_TYPE_ICONS[row.map_type];

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-2 font-medium',
        GAME_MAP_TYPE_TEXT_STYLES[row.map_type]
      )}
    >
      {icon && <i className={icon} aria-hidden="true" />}
      {row.name}
    </span>
  );
};

export const renderGameMapListTypeCell = (
  row: GameMapDefinition
): ReactNode => {
  const icon = GAME_MAP_TYPE_ICONS[row.map_type];

  return (
    <span
      className={clsx(
        'inline-flex items-center gap-2 text-sm font-medium',
        GAME_MAP_TYPE_TEXT_STYLES[row.map_type]
      )}
    >
      {icon && <i className={icon} aria-hidden="true" />}
      {row.map_type_label}
    </span>
  );
};

export const renderGameMapListPlaneCell = (
  row: GameMapDefinition
): ReactNode => (
  <span className="text-gray-700 dark:text-gray-300">{row.plane}</span>
);

export const renderGameMapListSourceCell = (
  row: GameMapDefinition
): ReactNode => (
  <span className="text-sm text-gray-500 dark:text-gray-400">
    {row.source ?? '—'}
  </span>
);
