import { isNil } from 'lodash';
import { useEffect } from 'react';

import UseManageMovementDefinition from './definitions/use-manage-movement-definition';
import UseManageMovementParams from './definitions/use-manage-movement-params';
import { useProcessDirectionalMovement } from './use-process-directional-movement';
import { useDirectionallyMoveCharacter } from '../../actions/partials/floating-cards/map-section/hooks/use-directionally-move-character';
import { useManageMapMovementErrorState } from '../../actions/partials/floating-cards/map-section/hooks/use-manage-map-movement-error-state';
import { MapApiUrls } from '../api/enums/map-api-urls';
import { useMoveCharacterDirectionallyApi } from '../api/hooks/use-move-character-directionally-api';

export const useManageMovement = ({
  setCharacterMapPosition,
  characterMapPosition,
  characterData,
}: UseManageMovementParams): UseManageMovementDefinition => {
  const { movementAmount, movementType, resetMovementAmount } =
    useDirectionallyMoveCharacter();

  const { showMessage } = useManageMapMovementErrorState();

  const { setRequestParams, error: movementError } =
    useMoveCharacterDirectionallyApi({
      url: MapApiUrls.MOVE_CHARACTER_DIRECTIONALLY,
      callback: setCharacterMapPosition,
      handleResetMapMovement: resetMovementAmount,
      characterData,
    });

  const { setUpdateCharacterPosition, updatePosition } =
    useProcessDirectionalMovement({
      onPositionChange: setRequestParams,
    });

  useEffect(
    () => {
      if (!movementType) {
        return;
      }

      updatePosition({
        baseX: characterMapPosition.x,
        baseY: characterMapPosition.y,
        movementAmount,
        movementType,
      });

      setUpdateCharacterPosition((prevValue) => !prevValue);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [movementAmount, movementType]
  );

  useEffect(() => {
    if (isNil(movementError)) {
      return;
    }

    showMessage(movementError.message);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [movementError]);

  return {
    movementError,
  };
};
