import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { isNil } from 'lodash';
import { useCallback, useEffect, useState } from 'react';

import { CharacterPosition } from './definitions/base-map-api-definition';
import BaseMapDetailsApiDefinition from './definitions/base-map-details-api-definition';
import MoveCharacterRequestDefinition from './definitions/move-character-request-definition';
import UseMoveCharacterDirectionallyApiDefinition from './definitions/use-move-character-directionally-api-definition';
import UseMoveCharacterDirectionallyApiParams from './definitions/use-move-character-directionally-api-params';
import { useDirectionallyMoveCharacter } from '../../../actions/partials/floating-cards/map-section/hooks/use-directionally-move-character';
import { useManageMapMovementErrorState } from '../../../actions/partials/floating-cards/map-section/hooks/use-manage-map-movement-error-state';

export const useMoveCharacterDirectionallyApi = (
  params: UseMoveCharacterDirectionallyApiParams
): UseMoveCharacterDirectionallyApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { showMessage } = useManageMapMovementErrorState();
  const { resetMovementAmount } = useDirectionallyMoveCharacter();

  const [error, setError] =
    useState<BaseMapDetailsApiDefinition['error']>(null);
  const [requestParams, setRequestParams] =
    useState<MoveCharacterRequestDefinition>({
      character_position_x: 0,
      character_position_y: 0,
    });

  let url = '';

  if (params.characterData) {
    url = getUrl(params.url, { character: params.characterData.id });
  }

  const moveCharacterDirectionally = useCallback(
    async () => {
      if (!params.characterData) {
        return;
      }

      try {
        const result = await apiHandler.post<
          CharacterPosition,
          AxiosRequestConfig<AxiosResponse<CharacterPosition>>,
          MoveCharacterRequestDefinition
        >(url, {
          character_position_x: requestParams.character_position_x,
          character_position_y: requestParams.character_position_y,
        });

        if (params.handleResetMapMovement) {
          params.handleResetMapMovement();
        }

        if (params.callback) {
          params.callback({
            x: result.x_position,
            y: result.y_position,
          });
        }
      } catch (err) {
        if (err instanceof AxiosError) {
          setError(err.response?.data);

          setRequestParams({
            character_position_x: 0,
            character_position_y: 0,
          });

          resetMovementAmount();
        }
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url, requestParams]
  );

  useEffect(
    () => {
      if (!params.characterData) {
        return;
      }

      if (
        requestParams.character_position_x < 16 &&
        requestParams.character_position_y < 16
      ) {
        return;
      }

      moveCharacterDirectionally().catch(() => {});
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [moveCharacterDirectionally, requestParams]
  );

  useEffect(
    () => {
      if (isNil(error)) {
        return;
      }

      showMessage(error.message);
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [error]
  );

  return {
    error,
    setRequestParams,
  };
};
