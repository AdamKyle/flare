import clsx from 'clsx';
import React, { PointerEvent as ReactPointerEvent, ReactNode } from 'react';

import GameMapMarkerProps from '../types/game-map-marker-props';
import { resolveGameMapMarkerPosition } from '../utils/resolve-game-map-marker-position';
import {
  GAME_MAP_MARKER_COLOR,
  GAME_MAP_MARKER_ICON,
} from '../values/game-map-marker-variants';

const GameMapMarker = ({
  variant,
  left,
  top,
  accessible_name: accessibleName,
  on_activate: onActivate,
}: GameMapMarkerProps): ReactNode => {
  const markerPosition = resolveGameMapMarkerPosition(left, top);

  const handlePointerDown = (
    event: ReactPointerEvent<HTMLButtonElement>
  ): void => {
    event.stopPropagation();
  };

  const handlePointerUp = (
    event: ReactPointerEvent<HTMLButtonElement>
  ): void => {
    event.stopPropagation();
  };

  const handleClick = (event: React.MouseEvent<HTMLButtonElement>): void => {
    event.stopPropagation();
    onActivate();
  };

  return (
    <button
      type="button"
      onPointerDown={handlePointerDown}
      onPointerUp={handlePointerUp}
      onClick={handleClick}
      aria-label={accessibleName}
      title={accessibleName}
      className={clsx(
        'focus-visible:ring-mango-tango-400 absolute m-0 flex size-6 items-center justify-center rounded-full border-0 bg-white p-0 leading-none shadow focus:outline-none focus-visible:ring-2 dark:bg-gray-900',
        GAME_MAP_MARKER_COLOR[variant]
      )}
      style={{
        left: markerPosition.left,
        top: markerPosition.top,
      }}
    >
      <i
        className={clsx(GAME_MAP_MARKER_ICON[variant], 'text-[11px]')}
        aria-hidden="true"
      />
    </button>
  );
};

export default GameMapMarker;
