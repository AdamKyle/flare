import { useActivityTimeout } from 'api-handler/hooks/use-activity-timeout';
import { useApiHandler } from 'api-handler/hooks/use-api-handler';
import { AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import ConjureCelestialRequestDefinition from '../definitions/conjure-celestial-request-definition';
import { ConjureApiUrls } from '../enums/conjure-api-urls';
import UseConjureCelestialApiDefinition from './definitions/use-conjure-celestial-api-definition';
import UseConjureCelestialApiParams from './definitions/use-conjure-celestial-api-params';
import { useCloseSidePeekEmitter } from '../../../../base/hooks/use-close-side-peek-emitter';

const UNABLE_TO_CONJURE_MESSAGE = 'Unable to conjure this celestial.';

export const useConjureCelestialApi = (
  params: UseConjureCelestialApiParams
): UseConjureCelestialApiDefinition => {
  const { apiHandler, getUrl } = useApiHandler();
  const { handleInactivity } = useActivityTimeout();
  const { closeSidePeek } = useCloseSidePeekEmitter();

  const [error, setError] =
    useState<UseConjureCelestialApiDefinition['error']>(null);
  const [loading, setLoading] = useState(false);

  const isMountedRef = useRef(true);
  const requestIdRef = useRef(0);

  useEffect(() => {
    isMountedRef.current = true;

    return () => {
      isMountedRef.current = false;
    };
  }, []);

  const url = getUrl(ConjureApiUrls.CONJURE, {
    character: params.character_id,
  });

  const conjureCelestial = useCallback(
    async (request: ConjureCelestialRequestDefinition) => {
      const requestId = ++requestIdRef.current;

      setLoading(true);
      setError(null);

      try {
        await apiHandler.post<never, never, ConjureCelestialRequestDefinition>(
          url,
          request
        );

        if (!isMountedRef.current || requestId !== requestIdRef.current) {
          return;
        }

        closeSidePeek();
      } catch (err) {
        if (!isMountedRef.current || requestId !== requestIdRef.current) {
          return;
        }

        if (!(err instanceof AxiosError)) {
          setError({ message: UNABLE_TO_CONJURE_MESSAGE });
          return;
        }

        if (err.response?.status === 401) {
          handleInactivity({ setError, response: err });
          return;
        }

        setError(err.response?.data ?? { message: UNABLE_TO_CONJURE_MESSAGE });
      } finally {
        if (isMountedRef.current && requestId === requestIdRef.current) {
          setLoading(false);
        }
      }
    },
    [apiHandler, url, closeSidePeek, handleInactivity]
  );

  return {
    conjureCelestial,
    loading,
    error,
  };
};
