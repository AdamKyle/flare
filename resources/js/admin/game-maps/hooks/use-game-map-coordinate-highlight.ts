import { useEffect, useState } from 'react';

import UseGameMapCoordinateHighlightDefinition from './definitions/use-game-map-coordinate-highlight-definition';
import CoordinateDefinition from '../types/coordinate-definition';
import SelectedCoordinateDefinition from '../types/selected-coordinate-definition';

export const useGameMapCoordinateHighlight = (
  selectedCoordinate: SelectedCoordinateDefinition | null
): UseGameMapCoordinateHighlightDefinition => {
  const [highlighted, setHighlighted] = useState<CoordinateDefinition | null>(
    selectedCoordinate?.coordinate ?? null
  );

  useEffect(() => {
    if (selectedCoordinate) {
      setHighlighted(selectedCoordinate.coordinate);
    }
  }, [selectedCoordinate]);

  return {
    highlighted,
    set_highlighted: setHighlighted,
  };
};
