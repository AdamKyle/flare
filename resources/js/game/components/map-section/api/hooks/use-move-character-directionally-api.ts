import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig, AxiosResponse } from 'axios';
import { useCallback, useEffect, useState } from 'react';

import { CharacterPosition } from './definitions/base-map-api-definition';
import BaseMapDetailsApiDefinition from './definitions/base-map-details-api-definition';
import MapDetailsApiRequestParams from './definitions/map-details-api-request-params';
import MoveCharacterRequestDefinition from './definitions/move-character-request-definition';
import UseMoveCharacterDirectionallyApiDefinition from './definitions/use-move-character-directionally-api-definition';

export const useMoveCharacterDirectionallyApi = (
  params: MapDetailsApiRequestParams
): UseMoveCharacterDirectionallyApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
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
          setError(err.response?.data || null);
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

  return {
    error,
    setRequestParams,
  };
};
