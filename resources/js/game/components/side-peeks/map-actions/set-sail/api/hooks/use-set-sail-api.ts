import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { useEmitCharacterPosition } from '../../../../../map-section/hooks/use-emit-character-position';
import { useCloseSidePeekEmitter } from '../../../../base/hooks/use-close-side-peek-emitter';
import { useEmitMapRefresh } from '../../../traverse/hooks/use-emit-map-refresh';
import SetSailRequestDefinition from '../definitions/set-sail-request-definition';
import SetSailResponseDefinition from '../definitions/set-sail-response-definition';
import { SetSailApiUrls } from '../enums/set-sail-api-urls';
import UseSetSailApiDefinition from './definitions/use-set-sail-api-definition';
import UseSetSailApiParams from './definitions/use-set-sail-api-params';

const UNABLE_TO_SET_SAIL_MESSAGE = 'Unable to set sail.';

export const useSetSailApi = (
  params: UseSetSailApiParams
): UseSetSailApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const { emitCharacterPosition } = useEmitCharacterPosition();
  const { emitShouldRefreshMap } = useEmitMapRefresh();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const [error, setError] = useState<UseSetSailApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);

  const isMountedRef = useRef(true);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  const url = getUrl(SetSailApiUrls.SET_SAIL, {
    character: params.character_id,
  });

  const setSail = useCallback(
    async (request: SetSailRequestDefinition) => {
      setLoading(true);
      setError(null);

      try {
        const result = await apiHandler.post<
          SetSailResponseDefinition,
          never,
          SetSailRequestDefinition
        >(url, request);

        if (!isMountedRef.current) {
          return;
        }

        emitCharacterPosition({
          x: result.character_position_data.x_position,
          y: result.character_position_data.y_position,
        });

        if (result.has_traversed) {
          emitShouldRefreshMap(true);
        }

        closeSidePeek();
      } catch (err) {
        if (!isMountedRef.current) {
          return;
        }

        if (!(err instanceof AxiosError)) {
          setError({ message: UNABLE_TO_SET_SAIL_MESSAGE });
          return;
        }

        if (err.response?.status === 401) {
          handleInactivity({ setError, response: err });
          return;
        }

        setError(err.response?.data ?? { message: UNABLE_TO_SET_SAIL_MESSAGE });
      } finally {
        if (isMountedRef.current) {
          setLoading(false);
        }
      }
    },
    [
      apiHandler,
      url,
      emitCharacterPosition,
      emitShouldRefreshMap,
      closeSidePeek,
      handleInactivity,
    ]
  );

  return {
    setSail,
    loading,
    error,
  };
};
