import {
  Dispatch,
  KeyboardEvent as ReactKeyboardEvent,
  SetStateAction,
} from 'react';

import { GameMapEditorValues } from '../enums/game-map-editor-values';
import GameMapKeyboardNavigationDefinition from '../types/game-map-keyboard-navigation-definition';
import CoordinateDefinition from '../types/coordinate-definition';
import { coordinateAtIndex } from './resolve-pointer-coordinate';

export const resolveGameMapKeyboardNavigation = (
  xValues: number[],
  yValues: number[],
  highlighted: CoordinateDefinition | null,
  setHighlighted: Dispatch<SetStateAction<CoordinateDefinition | null>>,
  panIntoView: (coordinate: CoordinateDefinition) => void,
  activateCoordinate: (coordinate: CoordinateDefinition) => void,
  cancelActiveMode: () => void
): GameMapKeyboardNavigationDefinition => {
  const hasSelectableCoordinates = xValues.length > 0 && yValues.length > 0;

  const moveHighlight = (xIndex: number, yIndex: number): void => {
    const clampedX = Math.min(Math.max(xIndex, 0), xValues.length - 1);
    const clampedY = Math.min(Math.max(yIndex, 0), yValues.length - 1);
    const coordinate = coordinateAtIndex(clampedX, clampedY, xValues, yValues);

    setHighlighted(coordinate);
    panIntoView(coordinate);
  };

  const handleKeyDown = (event: ReactKeyboardEvent<HTMLDivElement>): void => {
    if (!hasSelectableCoordinates) {
      return;
    }

    const current = highlighted ?? coordinateAtIndex(0, 0, xValues, yValues);

    if (event.key === 'ArrowRight') {
      event.preventDefault();
      moveHighlight(current.x_index + 1, current.y_index);

      return;
    }

    if (event.key === 'ArrowLeft') {
      event.preventDefault();
      moveHighlight(current.x_index - 1, current.y_index);

      return;
    }

    if (event.key === 'ArrowDown') {
      event.preventDefault();
      moveHighlight(current.x_index, current.y_index + 1);

      return;
    }

    if (event.key === 'ArrowUp') {
      event.preventDefault();
      moveHighlight(current.x_index, current.y_index - 1);

      return;
    }

    if (event.key === 'Home') {
      event.preventDefault();
      moveHighlight(0, current.y_index);

      return;
    }

    if (event.key === 'End') {
      event.preventDefault();
      moveHighlight(xValues.length - 1, current.y_index);

      return;
    }

    if (event.key === 'PageUp') {
      event.preventDefault();
      moveHighlight(
        current.x_index,
        current.y_index - GameMapEditorValues.PageRowStep
      );

      return;
    }

    if (event.key === 'PageDown') {
      event.preventDefault();
      moveHighlight(
        current.x_index,
        current.y_index + GameMapEditorValues.PageRowStep
      );

      return;
    }

    if (event.key === 'Enter') {
      event.preventDefault();
      activateCoordinate(current);

      return;
    }

    if (event.key === 'Escape') {
      event.preventDefault();
      cancelActiveMode();
    }
  };

  return {
    handle_key_down: handleKeyDown,
  };
};
