import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError, AxiosRequestConfig } from 'axios';
import { isNil } from 'lodash';
import { useCallback, useEffect, useState } from 'react';

import UseTeleportPlayerApiParams from './definitions/use-teleport-player-api-params';
import BaseMapDetailsApiDefinition from '../../../../../map-section/api/hooks/definitions/base-map-details-api-definition';
import { TeleportApiUrls } from '../enums/teleport-api-urls';
import TeleportCharacterRequestDefinition from './definitions/teleport-character-request-definition';
import { UseTeleportPlayerApiDefinition } from './definitions/use-teleport-player-api-definition';
import { useManageMapMovementErrorState } from '../../../../../actions/partials/floating-cards/map-section/hooks/use-manage-map-movement-error-state';
import { CharacterPosition } from '../../../../../map-section/api/hooks/definitions/base-map-api-definition';
import { useEmitCharacterPosition } from '../../../../../map-section/hooks/use-emit-character-position';
import { useCloseSidePeekEmitter } from '../../../../base/hooks/use-close-side-peek-emitter';

export const useTeleportPlayerApi = (
  params: UseTeleportPlayerApiParams
): UseTeleportPlayerApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { emitCharacterPosition } = useEmitCharacterPosition();
  const { showMessage } = useManageMapMovementErrorState();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const [error, setError] =
    useState<BaseMapDetailsApiDefinition['error']>(null);
  const [requestParams, setRequestParams] =
    useState<TeleportCharacterRequestDefinition>({
      x: 0,
      y: 0,
      cost: 0,
      timeout: 0,
    });

  const url = getUrl(TeleportApiUrls.TELEPORT_PLAYER, {
    character: params.character_id,
  });

  const teleportPlayer = useCallback(
    async () => {
      if (requestParams.cost <= 0) {
        return;
      }

      try {
        const result = await apiHandler.post<
          CharacterPosition,
          AxiosRequestConfig<CharacterPosition>,
          TeleportCharacterRequestDefinition
        >(url, {
          x: requestParams.x,
          y: requestParams.y,
          cost: requestParams.cost,
          timeout: requestParams.timeout,
        });

        emitCharacterPosition({
          x: result.x_position,
          y: result.y_position,
        });

        closeSidePeek();
      } catch (error) {
        if (error instanceof AxiosError) {
          setError(error.response?.data);
        }
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [apiHandler, url, requestParams]
  );

  useEffect(() => {
    teleportPlayer().catch(() => {});
  }, [teleportPlayer, requestParams]);

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
