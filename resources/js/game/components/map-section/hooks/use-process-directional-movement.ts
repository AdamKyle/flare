import { useEffect, useState } from 'react';

import UpdatePositionParams from './definitions/update-position-params';
import UseProcessDirectionalMovementDefinition from './definitions/use-process-directional-movement-definition';
import UseProcessDirectionalMovementParams from './definitions/use-process-directional-movement-params';
import { MapMovementTypes } from '../../actions/partials/floating-cards/map-section/map-movement-types/map-movement-types';

export const useProcessDirectionalMovement = ({
  onPositionChange,
  onCharacterPositionChange,
  handleResetMovement,
}: UseProcessDirectionalMovementParams): UseProcessDirectionalMovementDefinition => {
  const [updateCharacterPosition, setUpdateCharacterPosition] = useState(false);

  const [characterMapPosition, setCharacterMapPosition] = useState<{
    x: number;
    y: number;
  }>({
    x: 0,
    y: 0,
  });

  useEffect(() => {
    if (!updateCharacterPosition) {
      return;
    }

    onPositionChange({
      character_position_x: characterMapPosition.x,
      character_position_y: characterMapPosition.y,
    });

    handleResetMovement();
  }, [updateCharacterPosition]);

  useEffect(() => {
    onCharacterPositionChange({
      x: characterMapPosition.x,
      y: characterMapPosition.y,
    });
  }, [characterMapPosition]);

  const updatePosition = ({
    baseX,
    baseY,
    movementAmount,
    movementType,
  }: UpdatePositionParams) => {
    if (!movementType) {
      setCharacterMapPosition({
        x: baseX,
        y: baseY,
      });

      return;
    }

    if (movementType === MapMovementTypes.EAST) {
      baseX += movementAmount;
    }
    if (movementType === MapMovementTypes.WEST) {
      baseX += movementAmount;
    }
    if (movementType === MapMovementTypes.NORTH) {
      baseY += movementAmount;
    }
    if (movementType === MapMovementTypes.SOUTH) {
      baseY += movementAmount;
    }

    setCharacterMapPosition({
      x: baseX,
      y: baseY,
    });
  };

  return {
    updatePosition,
    setUpdateCharacterPosition,
  };
};
